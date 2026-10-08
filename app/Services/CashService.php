<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\PointOfSale;
use App\Models\User;
use App\Support\SaleOperation as Op;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class CashService
{
    public function active(int $user, int $shop): CashSession
    {
        User::whereKey($user)->lockForUpdate()->firstOrFail();
        $s = CashSession::where('active_user_id', $user)->where('point_of_sale_id', $shop)->lockForUpdate()->first();
        if (! $s) {
            Op::fail('cash_session', 'Open a cash session in this store before recording an operation.');
        }

        return $s;
    }

    public function expected(CashSession $s): BigDecimal
    {
        $in = CashMovement::where('cash_session_id', $s->id)->where('direction', 'in')->sum('amount');
        $out = CashMovement::where('cash_session_id', $s->id)->where('direction', 'out')->sum('amount');

        return BigDecimal::of($s->opening_amount)->plus((string) $in)->minus((string) $out);
    }

    public function open(int $user, int $shop, array $data): CashSession
    {
        return DB::transaction(function () use ($user, $shop, $data) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, $data);
            if ($old = CashSession::where('operation_key', $key)->first()) {
                Op::replay($old, $hash);

                return $old;
            }
            if (CashSession::where('active_user_id', $user)->exists()) {
                Op::fail('cash_session', 'You already have an open cash session.');
            }
            $opening = Op::money($data['opening_amount']);
            $from = null;
            if (! empty($data['handover_from_id'])) {
                $from = CashSession::whereKey($data['handover_from_id'])->where('point_of_sale_id', $shop)->where('handover_to_user_id', $user)->where('state', 'closed')->lockForUpdate()->firstOrFail();
                if (! BigDecimal::of($from->counted_amount)->isEqualTo($opening)) {
                    Op::fail('opening_amount', 'The handover amount must match the previous count.');
                }
            }

            return CashSession::create(['point_of_sale_id' => $shop, 'user_id' => $user, 'active_user_id' => $user, 'opening_amount' => (string) $opening, 'opened_at' => now('UTC'), 'operation_key' => $key, 'request_hash' => $hash, 'handover_from_id' => $from?->id]);
        }, 3);
    }

    public function close(int $user, int $shop, int $id, array $data): CashSession
    {
        return DB::transaction(function () use ($user, $shop, $id, $data) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $s = CashSession::whereKey($id)->where('user_id', $user)->where('point_of_sale_id', $shop)->lockForUpdate()->firstOrFail();
            $count = Op::money($data['counted_amount']);
            $target = $data['handover_to_user_id'] ?? null;
            if ($target) {
                $next = User::findOrFail($target);
                if ($next->is_suspended || ! $next->can('cash_session_manage') || ! PointOfSale::accessibleBy($next)->whereKey($shop)->exists() || (int) $target === $user) {
                    Op::fail('handover_to_user_id', 'Choose an authorized cashier for this store.');
                }
            }
            if ($s->state === 'closed') {
                if (! BigDecimal::of($s->counted_amount)->isEqualTo($count) || (int) $s->handover_to_user_id !== (int) $target || $s->reason !== ($data['reason'] ?? null)) {
                    Op::fail('cash_session', 'This session is already closed with another count.');
                }

                return $s;
            }
            $expected = $this->expected($s);
            $diff = $count->minus($expected);
            if (! $diff->isZero() && empty(trim($data['reason'] ?? ''))) {
                Op::fail('reason', 'Explain the cash difference.');
            }
            $s->update(['state' => 'closed', 'active_user_id' => null, 'counted_amount' => (string) $count, 'expected_amount' => (string) $expected, 'difference' => (string) $diff, 'closed_at' => now('UTC'), 'handover_to_user_id' => $target, 'reason' => $data['reason'] ?? null]);

            return $s;
        }, 3);
    }

    public function movement(CashSession $s, int $user, string $direction, string $amount, string $kind, string $key, string $reason, ?int $payment = null): CashMovement
    {
        if ($s->state !== 'open' || (int) $s->user_id !== $user) {
            Op::fail('cash_session', 'The cash session is closed or belongs to another cashier.');
        }
        $v = Op::money($amount);
        if (! $v->isPositive() || ! in_array($direction, ['in', 'out'], true)) {
            Op::fail('amount', 'Enter a positive cash amount.');
        }
        $hash = Op::hash($user, (int) $s->point_of_sale_id, [$s->id, $direction, (string) $v, $kind, $reason, $payment]);
        if ($old = CashMovement::where('operation_key', $key)->first()) {
            Op::replay($old, $hash);

            return $old;
        }
        if ($direction === 'out' && $this->expected($s)->isLessThan($v)) {
            Op::fail('amount', 'Insufficient expected cash in this session.');
        }

        return CashMovement::create(['cash_session_id' => $s->id, 'user_id' => $user, 'payment_id' => $payment, 'direction' => $direction, 'amount' => (string) $v, 'kind' => $kind, 'operation_key' => $key, 'request_hash' => $hash, 'reason' => $reason, 'occurred_at' => now('UTC')]);
    }
}

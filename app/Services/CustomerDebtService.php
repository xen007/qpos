<?php

namespace App\Services;

use App\Models\Order;
use App\Support\SaleOperation as Op;
use App\Support\SaleTransaction;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class CustomerDebtService
{
    public function record(int $user, int $shop, int $id, string $kind, array $data): void
    {
        SaleTransaction::run(function () use ($user, $shop, $id, $kind, $data) {
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, [$id, $kind, $data]);
            if ($old = DB::table('customer_debt_events')->where('operation_key', $key)->first()) {
                Op::replay($old, $hash);

                return;
            }
            $o = Order::whereKey($id)->where('point_of_sale_id', $shop)->lockForUpdate()->firstOrFail();
            if ($o->sale_state === 'legacy' || $o->currency_code !== 'XAF' || $o->customer->isWalking() || ! BigDecimal::of($o->due)->isPositive()) {
                Op::fail('order', 'Only a native outstanding sale can have its due date changed.');
            }
            if ($kind === 'due_date_change') {
                $payload = ['before' => $o->due_date, 'after' => $data['due_date']];
                $o->update(['due_date' => $data['due_date']]);
            } else {
                $payload = ['method' => $data['method'], 'note' => $data['note']];
            }
            DB::table('customer_debt_events')->insert(['order_id' => $id, 'user_id' => $user, 'point_of_sale_id' => $shop, 'kind' => $kind, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'operation_key' => $key, 'request_hash' => $hash, 'occurred_at' => now('UTC'), 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        });
    }
}

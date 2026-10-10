<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Support\QuantityDecimal;
use App\Support\SaleOperation as Op;
use App\Support\SaleTransaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class SaleCorrectionService
{
    public function preview(int $user, int $shop, int $id, array $items): array
    {
        $o = Order::whereKey($id)->where('point_of_sale_id', $shop)->firstOrFail();
        $lines = $o->products()->get();
        $value = BigDecimal::zero();
        $fingerprint = [];
        foreach ($lines as $line) {
            $value = $value->plus($line->return_value_used);
        }
        if (empty($items) || count(array_unique(array_column($items, 'order_product_id'))) !== count($items)) {
            Op::fail('items', 'Select distinct return lines.');
        }
        foreach ($items as $item) {
            $line = $lines->firstWhere('id', (int) $item['order_product_id']);
            if (! $line) {
                Op::fail('items', 'The return line does not belong to this sale.');
            }
            $q = QuantityDecimal::parse($item['quantity'], 'quantity', true);
            $next = BigDecimal::of($line->returned_quantity)->plus($q);
            if ($next->isGreaterThan($line->quantity)) {
                Op::fail('quantity', 'The returned quantity exceeds the remaining sale quantity.');
            }
            if ($line->effective_total === null) {
                Op::fail('order', 'Historical returns require a reconciled sale and stock allocations.');
            }
            $cumulative = $next->isEqualTo($line->quantity) ? BigDecimal::of($line->effective_total) : BigDecimal::of($line->effective_total)->multipliedBy($next)->dividedBy($line->quantity, 6, RoundingMode::Down);
            $value = $value->plus($cumulative->minus($line->return_value_used));
            $fingerprint[] = [$line->id, (string) $q, $line->returned_quantity, $line->return_value_used];
        }
        $amount = $value->toScale(0, RoundingMode::HalfUp)->minus($o->corrected_total);
        $debt = BigDecimal::min(BigDecimal::of($o->due), $amount);

        return ['amount' => (string) $amount, 'settled_amount' => (string) $amount->minus($debt), 'debt_reduction' => (string) $debt, 'return_quote_hash' => Op::hash($user, $shop, [$id, $o->due, $o->corrected_total, $fingerprint])];
    }

    public function correct(int $user, int $shop, int $id, array $data): object
    {
        return SaleTransaction::run(function () use ($user, $shop, $id, $data) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, [$id, $data]);
            if ($old = DB::table('sale_corrections')->where('operation_key', $key)->first()) {
                Op::replay($old, $hash);

                return $old;
            }
            $s = app(CashService::class)->active($user, $shop);
            $o = Order::whereKey($id)->where('point_of_sale_id', $shop)->lockForUpdate()->firstOrFail();
            if ($o->sale_state === 'legacy' || $o->currency_code !== 'XAF') {
                Op::fail('order', 'Historical returns require a reconciled sale and stock allocations.');
            }
            if ($o->sale_state === 'cancelled') {
                Op::fail('order', 'This sale is already cancelled.');
            }
            Customer::whereKey($o->customer_id)->lockForUpdate()->firstOrFail();
            if (($data['kind'] ?? '') === 'exchange' && ! hash_equals($this->preview($user, $shop, $id, $data['items'] ?? [])['return_quote_hash'], $data['return_quote_hash'] ?? '')) {
                Op::fail('exchange_sale', 'The return balance changed. Review the exchange before checkout.');
            }
            $reason = trim($data['reason'] ?? '');
            if ($reason === '') {
                Op::fail('reason', 'A reason is required.');
            }
            $kind = $data['kind'] ?? '';
            if (! in_array($kind, ['refund', 'credit_note', 'exchange', 'cancel'], true)) {
                Op::fail('kind', 'Choose a correction workflow.');
            }
            if ($o->customer->isWalking() && $kind === 'credit_note') {
                Op::fail('customer_id', 'Walking customers cannot receive credit. Use refund and a new cash sale.');
            }
            $items = $data['items'] ?? [];
            $lines = $o->products()->orderBy('product_id')->orderBy('id')->lockForUpdate()->get();
            Product::whereIn('id', $lines->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            if ($kind === 'cancel') {
                if ($lines->contains(fn ($l) => BigDecimal::of($l->returned_quantity)->isPositive())) {
                    Op::fail('order', 'A partially returned sale cannot be cancelled.');
                }
                $items = $lines->map(fn ($l) => ['order_product_id' => $l->id, 'quantity' => (string) $l->quantity, 'saleable' => (bool) ($data['saleable'] ?? false)])->all();
            }
            if (empty($items) || count(array_unique(array_column($items, 'order_product_id'))) !== count($items)) {
                Op::fail('items', 'Select distinct return lines.');
            }
            $prepared = [];
            foreach ($items as $input) {
                $line = $lines->firstWhere('id', (int) $input['order_product_id']);
                if (! $line) {
                    Op::fail('items', 'The return line does not belong to this sale.');
                }
                $q = QuantityDecimal::parse($input['quantity'], 'quantity', true);
                $previous = BigDecimal::of($line->returned_quantity);
                $next = $previous->plus($q);
                $original = BigDecimal::of($line->quantity);
                if ($next->isGreaterThan($original)) {
                    Op::fail('quantity', 'The returned quantity exceeds the remaining sale quantity.');
                }
                $base = QuantityDecimal::toBase((string) $q, $line->factor_used, (bool) $line->product->allows_fractional);
                $cumulative = $next->isEqualTo($original) ? BigDecimal::of($line->effective_total) : BigDecimal::of($line->effective_total)->multipliedBy($next)->dividedBy($original, 6, RoundingMode::Down);
                $value = $cumulative->minus($line->return_value_used);
                $line->update(['returned_quantity' => (string) $next, 'return_value_used' => (string) $cumulative]);
                $prepared[] = [$line, (string) $q, $base, (string) $value, (bool) ($input['saleable'] ?? false)];
            }
            $cumulative = BigDecimal::zero();
            foreach ($lines as $l) {
                $cumulative = $cumulative->plus($l->return_value_used);
            }
            $amount = $cumulative->toScale(0, RoundingMode::HalfUp)->minus($o->corrected_total);
            $debt = BigDecimal::min(BigDecimal::of($o->due), $amount);
            $settled = $amount->minus($debt);
            $snapshot = ['order_id' => $o->id, 'customer_label' => $o->customer->name, 'original_total' => $o->total, 'correction_amount' => (string) $amount, 'debt_reduction' => (string) $debt, 'settled_amount' => (string) $settled, 'currency_code' => 'XAF'];
            $now = now('UTC');
            $correction = DB::table('sale_corrections')->insertGetId(['order_id' => $id, 'cash_session_id' => $s->id, 'user_id' => $user, 'kind' => $kind, 'amount' => (string) $amount, 'debt_reduction' => (string) $debt, 'settled_amount' => (string) $settled, 'operation_key' => $key, 'request_hash' => $hash, 'reason' => $reason, 'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'created_at' => $now, 'updated_at' => $now]);
            foreach ($prepared as [$line,$q,$base,$value,$sellable]) {
                $returnId = DB::table('return_items')->insertGetId(['sale_correction_id' => $correction, 'order_product_id' => $line->id, 'quantity' => $q, 'base_quantity' => $base, 'amount' => $value, 'saleable' => $sellable, 'created_at' => $now, 'updated_at' => $now]);
                $left = BigDecimal::of($base);
                foreach ($line->stockAllocations()->orderBy('id')->lockForUpdate()->get() as $allocation) {
                    if ($left->isZero()) {
                        break;
                    }
                    $used = DB::table('return_stock_allocations')->where('order_stock_allocation_id', $allocation->id)->sum('quantity');
                    $available = BigDecimal::of($allocation->base_quantity)->minus((string) $used);
                    $take = BigDecimal::min($left, $available);
                    if (! $take->isPositive()) {
                        continue;
                    }
                    $batch = $allocation->product_batch_id ? ProductBatch::whereKey($allocation->product_batch_id)->lockForUpdate()->firstOrFail() : null;
                    if ($sellable && $batch && $batch->expiry_status === 'dated' && $batch->expires_on->toDateString() < now('Africa/Douala')->toDateString()) {
                        Op::fail('saleable', 'Expired products must return to unsaleable stock.');
                    }
                    $move = app(StockService::class)->increase($shop, $line->product_id, (string) $take, ['type' => 'return', 'bucket' => $sellable ? 'saleable' : 'unsaleable', 'batch_id' => $allocation->product_batch_id, 'correlation_key' => 'return-'.$returnId.'-'.$allocation->id, 'user_id' => $user, 'reason' => $reason]);
                    DB::table('return_stock_allocations')->insert(['return_item_id' => $returnId, 'order_stock_allocation_id' => $allocation->id, 'stock_movement_id' => $move->id, 'quantity' => (string) $take]);
                    $left = $left->minus($take);
                }
                if ($left->isPositive()) {
                    Op::fail('stock', 'The sale stock allocations are incomplete.');
                }
            }
            $exchangeValue = BigDecimal::zero();
            if ($kind === 'exchange') {
                $new = $data['exchange_sale'] ?? [];
                if ((int) ($new['customer_id'] ?? 0) !== (int) $o->customer_id) {
                    Op::fail('exchange_sale', 'The exchange must use the same customer.');
                }
                $newQuote = app(SaleService::class)->quote($user, $shop, $new);
                $exchangeValue = BigDecimal::min($settled, BigDecimal::of($newQuote['total'])->minus(Op::money($new['credit_amount'] ?? '0')));
                if ($exchangeValue->isNegative()) {
                    Op::fail('exchange_sale', 'Invalid exchange settlement.');
                }
            }
            $remainder = $settled->minus($exchangeValue);
            $snapshot['exchange_value'] = (string) $exchangeValue;
            $snapshot['remainder_amount'] = (string) $remainder;
            $snapshot['settlement_method'] = $kind === 'credit_note' || ($kind === 'exchange' && ! $o->customer->isWalking()) ? 'credit_note' : ($data['method'] ?? 'cash');
            if ($remainder->isPositive()) {
                if ($kind === 'credit_note' || ($kind === 'exchange' && ! $o->customer->isWalking())) {
                    DB::table('credit_notes')->insert(['customer_id' => $o->customer_id, 'point_of_sale_id' => $shop, 'sale_correction_id' => $correction, 'amount' => (string) $remainder, 'remaining_amount' => (string) $remainder, 'created_at' => $now, 'updated_at' => $now]);
                } else {
                    $method = $data['method'] ?? 'cash';
                    app(SaleService::class)->refund($o, $s, $method, $remainder, 'refund-'.$correction, $data['external_reference'] ?? null, $snapshot, $reason);
                }
            }
            $due = BigDecimal::of($o->due)->minus($debt);
            $corrected = BigDecimal::of($o->corrected_total)->plus($amount);
            $fully = $lines->every(fn ($l) => BigDecimal::of($l->quantity)->isEqualTo($l->returned_quantity));
            $o->update(['due' => (string) $due, 'corrected_total' => (string) $corrected, 'status' => $due->isZero(), 'is_returned' => $fully, 'sale_state' => $kind === 'cancel' ? 'cancelled' : ($fully ? 'returned' : 'partially_returned')]);
            if ($kind === 'exchange') {
                $sale = app(SaleService::class)->checkout($user, $shop, $new, ['correction_id' => $correction, 'amount' => (string) $exchangeValue]);
                if ($exchangeValue->isPositive()) {
                    DB::table('exchange_settlements')->insert(['sale_correction_id' => $correction, 'order_id' => $sale->id, 'amount' => (string) $exchangeValue, 'created_at' => $now, 'updated_at' => $now]);
                }
                DB::table('sale_corrections')->where('id', $correction)->update(['exchange_order_id' => $sale->id]);
            }
            app(SaleService::class)->assertBalance($o);
            DB::table('sale_corrections')->where('id', $correction)->update(['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);

            return DB::table('sale_corrections')->find($correction);
        });
    }
}

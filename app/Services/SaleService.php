<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PosCart;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\User;
use App\Support\MoneyDecimal;
use App\Support\SaleOperation as Op;
use App\Support\SaleTransaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function quote(int $user, int $shop, array $data): array
    {
        $customer = Customer::whereKey($data['customer_id'])->where('is_active', true)->firstOrFail();
        $cart = $data['cart_id'] ?? '';
        if (! preg_match('/\A[a-zA-Z0-9_-]{16,64}\z/D', $cart)) {
            Op::fail('cart_id', 'A valid cart identifier is required.');
        }
        $carts = PosCart::where('user_id', $user)->where('point_of_sale_id', $shop)->where('cart_id', $cart)->with(['product', 'productUnit'])->orderBy('product_id')->orderBy('product_unit_id')->get();
        $gross = BigDecimal::zero();
        $net = BigDecimal::zero();
        $lines = [];
        foreach ($carts as $row) {
            $unit = $row->productUnit;
            if (! $unit || $unit->product_id !== $row->product_id) {
                Op::fail('cart', 'Configure the packaging before checkout.');
            }
            $q = app(PricingService::class)->quote($unit, (string) $row->quantity, $shop, (int) $customer->id, now('Africa/Douala'));
            $q['reference_purchase_cost_snapshot'] = $unit->reference_purchase_cost;
            $q['tax_snapshot'] = ['state' => 'not_configured', 'rate' => null, 'base' => null, 'amount' => null];
            $gross = MoneyDecimal::rounded($gross->plus($q['gross_total']));
            $net = MoneyDecimal::rounded($net->plus($q['total_ttc']));
            $lines[] = ['id' => $row->id, 'product_id' => $row->product_id, 'product' => $row->product, 'quote' => $q, 'quantity' => $q['quantity'], 'row_total' => $q['total_ttc']];
        }
        $discount = MoneyDecimal::parse($data['order_discount'] ?? '0', 'order_discount');
        if ($discount->isGreaterThan($net)) {
            Op::fail('order_discount', 'The order discount cannot exceed the sale total.');
        }
        $exact = $net->minus($discount);
        $final = $exact->toScale(0, RoundingMode::HalfUp);
        MoneyDecimal::rounded($final);
        $snapshot = array_map(function ($line) {
            $q = $line['quote'];
            unset($q['calculated_at']);

            return [$line['id'], $line['product_id'], $q];
        }, $lines);

        return ['carts' => $lines, 'gross' => (string) $gross, 'total' => (string) $final, 'unrounded_total' => (string) $exact, 'rounding_adjustment' => (string) $final->minus($exact), 'line_net' => (string) $net, 'discount' => (string) $discount, 'walking' => $customer->isWalking(), 'quote_hash' => Op::hash($user, $shop, [$customer->id, $cart, $snapshot, (string) $discount])];
    }

    public function checkout(int $user, int $shop, array $data, ?array $exchangeSettlement = null): Order
    {
        return SaleTransaction::run(function () use ($user, $shop, $data, $exchangeSettlement) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, [$data, $exchangeSettlement]);
            if ($old = Order::where('operation_key', $key)->first()) {
                Op::replay($old, $hash);

                return $old;
            }
            $session = app(CashService::class)->active($user, $shop);
            $customer = Customer::whereKey($data['customer_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $ids = PosCart::where('user_id', $user)->where('point_of_sale_id', $shop)->where('cart_id', $data['cart_id'])->orderBy('product_id')->pluck('product_id')->unique();
            if ($ids->isEmpty()) {
                Op::fail('cart', 'The cart is empty.');
            }
            Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            ProductUnit::whereIn('product_id', $ids)->orderBy('id')->lockForUpdate()->get();
            ProductBatch::whereIn('product_id', $ids)->orderBy('product_id')->orderBy('id')->lockForUpdate()->get();
            $quote = $this->quote($user, $shop, $data);
            if (! hash_equals($quote['quote_hash'], $data['quote_hash'] ?? '')) {
                Op::fail('quote_hash', 'Prices or cart changed. Review the new total before checkout.');
            }
            if (BigDecimal::of($quote['discount'])->isPositive() && ! User::findOrFail($user)->can('sale_discount')) {
                Op::fail('order_discount', 'Manual discount permission is required.');
            }
            $total = BigDecimal::of($quote['total']);
            $credit = Op::money($data['credit_amount'] ?? '0', 'credit_amount');
            if ($credit->isGreaterThan($total) || ($customer->isWalking() && $credit->isPositive())) {
                Op::fail('credit_amount', 'This customer cannot use the requested credit.');
            }
            $cash = Op::money('0');
            $card = Op::money('0');
            $payments = $data['payments'] ?? [];
            foreach ($payments as $p) {
                $v = Op::money($p['amount']);
                if (! $v->isPositive()) {
                    Op::fail('payments', 'Payment lines must be positive.');
                }
                if ($p['method'] === 'cash') {
                    $cash = $cash->plus($v);
                } elseif ($p['method'] === 'card' && ! empty(trim($p['external_reference'] ?? ''))) {
                    $card = $card->plus($v);
                } else {
                    Op::fail('payments', 'Confirm the external card payment reference.');
                }
            }
            $exchangeValue = Op::money($exchangeSettlement['amount'] ?? '0');
            if ($exchangeValue->isGreaterThan($total->minus($credit))) {
                Op::fail('exchange', 'The exchange settlement exceeds the replacement sale.');
            }
            $remaining = $total->minus($credit)->minus($exchangeValue);
            if ($card->isGreaterThan($remaining)) {
                Op::fail('payments', 'Card payments cannot exceed the amount due.');
            }
            $change = BigDecimal::max(BigDecimal::zero(), $cash->plus($card)->minus($remaining));
            if ($change->isGreaterThan($cash)) {
                Op::fail('payments', 'Change can only be returned from cash.');
            }
            $paid = $cash->plus($card)->minus($change);
            $due = $remaining->minus($paid);
            if ($due->isPositive() && ($customer->isWalking() || ! User::findOrFail($user)->can('customer_credit_manage'))) {
                Op::fail('customer_id', 'This sale must be paid in full.');
            }
            if ($due->isPositive() && empty($data['due_date'])) {
                Op::fail('due_date', 'An outstanding sale requires a due date.');
            }
            if ($due->isPositive() && $data['due_date'] < now('Africa/Douala')->toDateString()) {
                Op::fail('due_date', 'The due date cannot be in the past.');
            }
            $order = Order::create(['point_of_sale_id' => $shop, 'cash_session_id' => $session->id, 'customer_id' => $customer->id, 'user_id' => $user, 'operation_key' => $key, 'request_hash' => $hash, 'cart_id' => $data['cart_id'], 'sale_state' => 'completed', 'currency_code' => 'XAF', 'due_date' => $due->isPositive() ? $data['due_date'] : null, 'sub_total' => $quote['gross'], 'discount' => (string) BigDecimal::of($quote['gross'])->minus($quote['unrounded_total']), 'unrounded_total' => $quote['unrounded_total'], 'rounding_adjustment' => $quote['rounding_adjustment'], 'total' => (string) $total, 'paid' => (string) $paid, 'due' => (string) $due, 'credit_used' => (string) $credit, 'change_amount' => (string) $change, 'status' => $due->isZero()]);
            $order->update(['exchange_value' => (string) $exchangeValue, 'created_at' => now('UTC'), 'updated_at' => now('UTC'), 'checkout_snapshot' => ['due_date' => $order->due_date, 'customer_label' => $customer->name, 'sub_total' => $quote['gross'], 'discount' => $order->discount, 'paid' => (string) $paid, 'due' => (string) $due, 'change_amount' => (string) $change, 'credit_used' => (string) $credit, 'exchange_value' => (string) $exchangeValue, 'payments' => $payments]]);
            $allocated = BigDecimal::zero();
            $last = count($quote['carts']) - 1;
            foreach ($quote['carts'] as $index => $row) {
                $q = $row['quote'];
                $effective = $index === $last ? $total->minus($allocated) : (BigDecimal::of($quote['line_net'])->isZero() ? BigDecimal::zero() : BigDecimal::of($q['total_ttc'])->multipliedBy($total)->dividedBy($quote['line_net'], 6, RoundingMode::Down));
                $allocated = $allocated->plus($effective);
                $line = $order->products()->create(['product_id' => $row['product_id'], 'product_unit_id' => $q['product_unit_id'], 'quantity' => $q['quantity'], 'base_quantity' => $q['base_quantity'], 'factor_used' => $q['factor_used'], 'unit_id_snapshot' => $q['unit_id_snapshot'], 'unit_label_snapshot' => $q['unit_label_snapshot'], 'packaging_label_snapshot' => $q['packaging_label_snapshot'], 'product_label_snapshot' => $q['product_label'], 'pricing_snapshot' => $q, 'price' => $q['price_ttc'], 'purchase_price' => $q['reference_purchase_cost_snapshot'] ?? '0', 'sub_total' => $q['gross_total'], 'discount' => $q['discount_total'], 'total' => $q['total_ttc'], 'effective_total' => (string) $effective]);
                $stock = app(StockService::class);
                $confirmed = (bool) ($data['confirm_expired_sale'] ?? false);
                if (! $confirmed && BigDecimal::of($stock->available($shop, $row['product_id']))->isLessThan($q['base_quantity']) && BigDecimal::of($stock->available($shop, $row['product_id'], true))->isGreaterThanOrEqualTo($q['base_quantity'])) {
                    Op::fail('expired_confirmation_required', 'An expired batch is available. Confirm the sale and provide a reason to continue.');
                }
                $stock->decrease($shop, $row['product_id'], $q['base_quantity'], ['correlation_key' => 'sale:line:'.$line->id, 'order_product_id' => $line->id, 'user_id' => $user, 'confirm_expired' => $confirmed, 'reason' => $data['expired_sale_reason'] ?? null]);
            }
            if ($credit->isPositive()) {
                $this->useCredit($order, $credit, $user);
            }
            $changeLeft = $change;
            $paymentBalance = $remaining;
            foreach ($payments as $i => $p) {
                $amount = Op::money($p['amount']);
                $back = $p['method'] === 'cash' ? BigDecimal::min($amount, $changeLeft) : BigDecimal::zero();
                $changeLeft = $changeLeft->minus($back);
                $net = $amount->minus($back);
                if ($net->isPositive()) {
                    $afterPayment = $paymentBalance->minus($net);
                    $this->payment($order, $session, $p['method'], $amount, $back, 'incoming', 'sale-'.$order->id.'-'.$i, $p['external_reference'] ?? null, ['order_id' => $order->id, 'customer_label' => $customer->name, 'balance_before' => (string) $paymentBalance, 'balance_after' => (string) $afterPayment, 'total' => (string) $total, 'currency_code' => 'XAF']);
                    $paymentBalance = $afterPayment;
                }
            }
            PosCart::where('user_id', $user)->where('point_of_sale_id', $shop)->where('cart_id', $data['cart_id'])->delete();
            $this->assertBalance($order);

            return $order->fresh();
        });
    }

    private function useCredit(Order $order, BigDecimal $amount, int $user): void
    {
        $notes = DB::table('credit_notes')->where('customer_id', $order->customer_id)->where('point_of_sale_id', $order->point_of_sale_id)->where('currency_code', 'XAF')->where('remaining_amount', '>', 0)->where(fn ($q) => $q->whereNull('expires_on')->orWhere('expires_on', '>=', now('Africa/Douala')->toDateString()))->orderBy('id')->lockForUpdate()->get();
        $remaining = $amount;
        foreach ($notes as $note) {
            if ($remaining->isZero()) {
                break;
            } $take = BigDecimal::min($remaining, BigDecimal::of($note->remaining_amount));
            DB::table('credit_notes')->where('id', $note->id)->update(['remaining_amount' => (string) BigDecimal::of($note->remaining_amount)->minus($take), 'updated_at' => now('UTC')]);
            DB::table('credit_note_uses')->insert(['credit_note_id' => $note->id, 'order_id' => $order->id, 'user_id' => $user, 'amount' => (string) $take, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
            $remaining = $remaining->minus($take);
        }
        if ($remaining->isPositive()) {
            Op::fail('credit_amount', 'Insufficient credit in this store.');
        }
    }

    public function assertBalance(Order $order): void
    {
        $order->refresh();
        $net = BigDecimal::zero();
        foreach ($order->paymentAllocations()->with('payment')->get() as $a) {
            $net = $a->payment->direction === 'incoming' ? $net->plus($a->amount) : $net->minus($a->amount);
        }
        $issued = DB::table('credit_notes')->join('sale_corrections', 'sale_corrections.id', '=', 'credit_notes.sale_correction_id')->where('sale_corrections.order_id', $order->id)->sum('credit_notes.amount');
        $exchanges = DB::table('exchange_settlements')->join('sale_corrections', 'sale_corrections.id', '=', 'exchange_settlements.sale_correction_id')->where('sale_corrections.order_id', $order->id)->sum('exchange_settlements.amount');
        $due = BigDecimal::of($order->total)->minus($order->corrected_total)->minus($order->credit_used)->minus($order->exchange_value)->minus($net)->plus((string) $issued)->plus((string) $exchanges);
        if (! $due->isEqualTo($order->due) || $due->isNegative()) {
            Op::fail('balance', 'The sale balance does not match its financial journal.');
        }
    }

    public function payment(Order $order, CashSession $s, string $method, BigDecimal $amount, BigDecimal $change, string $direction, string $key, ?string $ref, array $snapshot, ?string $reason = null, ?string $commandHash = null): Payment
    {
        $net = $amount->minus($change);
        $p = Payment::create(['point_of_sale_id' => $order->point_of_sale_id, 'cash_session_id' => $s->id, 'user_id' => $s->user_id, 'customer_id' => $order->customer_id, 'direction' => $direction, 'method' => $method, 'source_amount' => (string) $amount, 'received_amount' => (string) $amount, 'change_amount' => (string) $change, 'net_amount' => (string) $net, 'currency_code' => 'XAF', 'external_reference' => $ref, 'idempotency_key' => $key, 'request_hash' => $commandHash ?? Op::hash($s->user_id, $order->point_of_sale_id, $snapshot), 'receipt_snapshot' => $snapshot, 'reason' => $reason, 'occurred_at' => now('UTC')]);
        PaymentAllocation::create(['payment_id' => $p->id, 'order_id' => $order->id, 'amount' => (string) $net]);
        if ($method === 'cash') {
            app(CashService::class)->movement($s, $s->user_id, $direction === 'incoming' ? 'in' : 'out', (string) $net, 'payment', 'cash-payment-'.$p->id, $reason ?? 'Sale payment #'.$order->id, $p->id);
        }

        return $p;
    }

    public function collect(int $user, int $shop, int $id, array $data): Payment
    {
        return SaleTransaction::run(function () use ($user, $shop, $id, $data) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            $key = Op::key($data);
            $hash = Op::hash($user, $shop, [$id, $data]);
            if ($old = Payment::where('idempotency_key', $key)->first()) {
                Op::replay($old, $hash);

                return $old;
            }
            $s = app(CashService::class)->active($user, $shop);
            $o = Order::whereKey($id)->where('point_of_sale_id', $shop)->lockForUpdate()->firstOrFail();
            if ($o->currency_code !== 'XAF' || $o->sale_state === 'legacy') {
                Op::fail('order', 'Historical debt requires an explicit currency and reconciliation before collection.');
            }
            $v = Op::money($data['amount']);
            if (! $v->isPositive() || $v->isGreaterThan($o->due)) {
                Op::fail('amount', 'The amount cannot exceed the remaining balance.');
            }
            if (! in_array($data['method'], ['cash', 'card'], true) || ($data['method'] === 'card' && empty(trim($data['external_reference'] ?? '')))) {
                Op::fail('method', 'Confirm the external card payment reference.');
            }
            $before = $o->due;
            $after = BigDecimal::of($before)->minus($v);
            $p = $this->payment($o, $s, $data['method'], $v, BigDecimal::zero(), 'incoming', $key, $data['external_reference'] ?? null, ['order_id' => $o->id, 'customer_label' => $o->customer->name, 'total' => $o->total, 'balance_before' => $before, 'balance_after' => (string) $after, 'currency_code' => 'XAF', 'command_hash' => $hash], null, $hash);
            $o->update(['paid' => (string) BigDecimal::of($o->paid)->plus($v), 'due' => (string) $after, 'status' => $after->isZero()]);
            $this->assertBalance($o);

            return $p->refresh();
        });
    }
}

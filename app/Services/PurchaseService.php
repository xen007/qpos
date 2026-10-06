<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Supplier;
use App\Support\MoneyDecimal;
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseService
{
    public function create(array $data, PointOfSale $shop, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $shop, $userId) {
            if (!$shop->fresh()->is_active) throw ValidationException::withMessages(['point_of_sale_id'=>__('Choose an active assigned shop.')]);
            $supplier = Supplier::query()->whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
            $hash = hash('sha256',json_encode(['shop'=>$shop->id,'user'=>$userId,'data'=>$data],JSON_THROW_ON_ERROR));
            $existing = Purchase::where('operation_key',$data['idempotency_key'])->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash,$hash)) throw ValidationException::withMessages(['idempotency_key'=>__('This purchase key was already used with different data.')]);
                return $existing->load(['items','supplier','receipts.items']);
            }
            if (!$supplier->is_active) throw ValidationException::withMessages(['supplier_id' => __('Select an active supplier.')]);
            $purchase = new Purchase();
            $purchase->fill([
                'point_of_sale_id' => $shop->id, 'supplier_id' => $supplier->id, 'user_id' => $userId,
                'date' => $data['date'], 'due_date' => $data['due_date'] ?? null, 'status' => 1,
                'receipt_status' => 'pending', 'payment_status' => 'unpaid',
                'currency_code' => 'XAF',
                'operation_key'=>$data['idempotency_key'], 'request_hash'=>$hash,
                'sub_total' => '0.000000', 'tax' => '0.000000', 'discount_value' => '0.000000',
                'discount_type' => 'fixed', 'shipping' => '0.000000', 'grand_total' => '0.000000',
            ])->save();
            $productIds = collect($data['items'])->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $productIds->count()) throw ValidationException::withMessages(['items' => __('One or more products are unavailable.')]);
            $lines = [];
            $subtotal = BigDecimal::zero();
            foreach ($data['items'] as $index => $input) {
                $product = $products->get((int)$input['product_id']);
                if ($product->allows_fractional === null) throw ValidationException::withMessages(["items.$index.product_id" => __('Set the fractional quantity rule on the product first.')]);
                $unit = ProductUnit::query()->whereKey($input['product_unit_id'])->where('product_id', $product->id)->where('is_active', true)->lockForUpdate()->firstOrFail();
                $quantity = QuantityDecimal::parse($input['quantity'], "items.$index.quantity", true);
                $base = BigDecimal::of(QuantityDecimal::toBase((string)$quantity, (string)$unit->factor, (bool)$product->allows_fractional));
                $sourceCost = MoneyDecimal::parse($input['unit_cost'], "items.$index.unit_cost");
                $lineAmount = MoneyDecimal::rounded($sourceCost->multipliedBy($quantity));
                $subtotal = $subtotal->plus($lineAmount);
                $item = PurchaseItem::create([
                    'purchase_id' => $purchase->id, 'product_id' => $product->id,
                    'product_unit_id' => $unit->id, 'unit_id_snapshot' => $unit->unit_id,
                    'unit_label_snapshot' => $unit->label, 'factor_used' => (string)$unit->factor,
                    'entered_quantity' => (string)$quantity, 'base_quantity' => (string)$base,
                    'source_unit_cost' => (string)$sourceCost, 'source_line_amount' => (string)$lineAmount,
                    'source_line_amount_exact' => (string)$sourceCost->multipliedBy($quantity),
                    'allows_fractional_snapshot' => (bool)$product->allows_fractional,
                    'purchase_price' => (string)$sourceCost,
                    'price' => (string)MoneyDecimal::parse($input['sale_price'], "items.$index.sale_price"),
                    'quantity' => (string)$quantity,
                ]);
                $received = $input['received_quantity'] ?? (string)$quantity;
                if (BigDecimal::of((string)$received)->isGreaterThan('0')) {
                    $lines[] = ['purchase_item_id' => $item->id, 'quantity' => (string)$received, 'unit_cost' => (string)$sourceCost,
                        'expiry_status' => $input['expiry_status'] ?? 'unknown', 'expires_on' => $input['expires_on'] ?? null];
                }
            }
            $tax = MoneyDecimal::parse($data['tax'] ?? '0', 'tax');
            $discount = MoneyDecimal::parse($data['discount'] ?? '0', 'discount');
            $shipping = MoneyDecimal::parse($data['shipping'] ?? '0', 'shipping');
            $beforeDiscount = $subtotal->plus($tax)->plus($shipping);
            if ($discount->isGreaterThan($beforeDiscount)) throw ValidationException::withMessages(['discount' => __('The discount cannot exceed the purchase total.')]);
            $grand = $beforeDiscount->minus($discount);
            MoneyDecimal::rounded($subtotal);
            MoneyDecimal::rounded($grand);
            $purchase->forceFill(['sub_total' => (string)$subtotal, 'tax' => (string)$tax, 'discount_value' => (string)$discount,
                'shipping' => (string)$shipping, 'grand_total' => (string)$grand,
                'payment_status'=>$supplier->is_internal ? 'not_applicable' : ($grand->isZero() ? 'paid' : 'unpaid')])->save();
            if ($lines) $this->receive($purchase, $lines, $shop, $userId, 'purchase:'.$purchase->id.':initial');
            return $purchase->fresh(['items', 'supplier', 'receipts.items']);
        }, 3);
    }

    public function amend(Purchase $purchase, array $data, PointOfSale $shop, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase,$data,$shop,$userId) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            abort_unless((int)$purchase->point_of_sale_id === (int)$shop->id,404);
            $hash = hash('sha256',json_encode(['purchase'=>$purchase->id,'shop'=>$shop->id,'user'=>$userId,'data'=>$data],JSON_THROW_ON_ERROR));
            $existing = DB::table('purchase_amendments')->where('idempotency_key',$data['idempotency_key'])->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash,$hash)) throw ValidationException::withMessages(['idempotency_key'=>__('This amendment key was already used with different data.')]);
                return $purchase->fresh();
            }
            if ($purchase->receipt_status !== 'pending' || !$purchase->currency_code || $purchase->receipts()->exists() || $purchase->paymentAllocations()->exists())
                throw ValidationException::withMessages(['purchase'=>__('Only an unreceived purchase without payments may be amended.')]);
            $items = $purchase->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($items->keys()->sort()->values()->all() !== collect($data['items'])->pluck('purchase_item_id')->map(fn($id)=>(int)$id)->sort()->values()->all())
                throw ValidationException::withMessages(['items'=>__('Supply every existing purchase line exactly once.')]);
            $snapshot = fn()=>['purchase'=>$purchase->getAttributes(),'items'=>$items->map(fn($item)=>$item->getAttributes())->values()->all()];
            $before = $snapshot(); $subtotal = BigDecimal::zero();
            foreach ($data['items'] as $input) {
                $item = $items->get((int)$input['purchase_item_id']);
                $quantity = QuantityDecimal::parse($input['quantity'],'quantity',true);
                $cost = MoneyDecimal::parse($input['unit_cost'],'unit_cost');
                $exact = $cost->multipliedBy($quantity);
                $amount = MoneyDecimal::rounded($exact);
                $base = QuantityDecimal::toBase((string)$quantity,(string)$item->factor_used,(bool)$item->allows_fractional_snapshot);
                $item->forceFill(['quantity'=>(string)$quantity,'entered_quantity'=>(string)$quantity,'base_quantity'=>$base,
                    'purchase_price'=>(string)$cost,'source_unit_cost'=>(string)$cost,'source_line_amount'=>(string)$amount,'source_line_amount_exact'=>(string)$exact])->save();
                $subtotal = $subtotal->plus($amount);
            }
            $grand = $subtotal->plus($purchase->tax)->plus($purchase->shipping)->minus($purchase->discount_value);
            MoneyDecimal::rounded($subtotal); MoneyDecimal::rounded($grand);
            $purchase->forceFill(['sub_total'=>(string)$subtotal,'grand_total'=>(string)$grand,'due_date'=>$data['due_date'] ?? null,
                'payment_status'=>$purchase->supplier->is_internal ? 'not_applicable' : ($grand->isZero() ? 'paid':'unpaid')])->save();
            DB::table('purchase_amendments')->insert(['purchase_id'=>$purchase->id,'user_id'=>$userId,'idempotency_key'=>$data['idempotency_key'],
                'request_hash'=>$hash,'before_values'=>json_encode($before,JSON_THROW_ON_ERROR),'after_values'=>json_encode($snapshot(),JSON_THROW_ON_ERROR),
                'reason'=>$data['reason'],'occurred_at'=>now('Africa/Douala'),'created_at'=>now(),'updated_at'=>now()]);
            return $purchase->fresh(['items','supplier']);
        },3);
    }

    public function receive(Purchase $purchase, array $lines, PointOfSale $shop, int $userId, string $key): PurchaseReceipt
    {
        return DB::transaction(function () use ($purchase, $lines, $shop, $userId, $key) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if (!in_array($purchase->receipt_status, ['pending','partial','received'], true))
                throw ValidationException::withMessages(['purchase' => __('Historical or cancelled purchases cannot be received again.')]);
            if (!$purchase->point_of_sale_id || (int)$purchase->point_of_sale_id !== (int)$shop->id)
                throw ValidationException::withMessages(['purchase' => __('The purchase belongs to a different shop.')]);
            $hash = hash('sha256', json_encode(['purchase_id'=>$purchase->id,'shop_id'=>$shop->id,'user_id'=>$userId,'lines'=>$lines], JSON_THROW_ON_ERROR));
            $existing = PurchaseReceipt::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash, $hash)) throw ValidationException::withMessages(['idempotency_key' => __('This receipt key was already used with different data.')]);
                return $existing->load('items');
            }
            $receipt = PurchaseReceipt::create(['purchase_id' => $purchase->id, 'point_of_sale_id' => $shop->id, 'user_id' => $userId,
                'idempotency_key' => $key, 'request_hash' => $hash, 'status' => 'received',
                'received_at' => now('Africa/Douala'), 'provenance' => 'purchase']);
            $productIds = $purchase->items()->whereIn('id',array_column($lines,'purchase_item_id'))->pluck('product_id')->unique()->sort()->values();
            Product::whereIn('id',$productIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($lines as $i => $line) {
                $item = PurchaseItem::query()->where('purchase_id', $purchase->id)->whereKey($line['purchase_item_id'])->lockForUpdate()->firstOrFail();
                $unit = $item->product_unit_id ? ProductUnit::query()->findOrFail($item->product_unit_id) : null;
                $factor = BigDecimal::of((string)($item->factor_used ?? $unit?->factor ?? '1'));
                $qty = QuantityDecimal::parse($line['quantity'], "items.$i.quantity", true);
                $product = Product::query()->findOrFail($item->product_id);
                $fractionalRule = $item->allows_fractional_snapshot ?? $product->allows_fractional;
                if ($fractionalRule === null) throw ValidationException::withMessages(["items.$i.quantity" => __('Set the fractional quantity rule on the product first.')]);
                $base = BigDecimal::of(QuantityDecimal::toBase((string)$qty, (string)$factor, (bool)$fractionalRule));
                $ordered = BigDecimal::of((string)($item->entered_quantity ?? $item->quantity));
                $already = BigDecimal::zero();
                foreach (PurchaseReceiptItem::query()->where('purchase_item_id', $item->id)->lockForUpdate()->get(['entered_quantity']) as $prior) $already = $already->plus($prior->entered_quantity);
                if ($already->plus($qty)->isGreaterThan($ordered)) throw ValidationException::withMessages(["items.$i.quantity" => __('Received quantity cannot exceed the outstanding ordered quantity.')]);
                $sourceCost = MoneyDecimal::parse($line['unit_cost'] ?? (string)($item->source_unit_cost ?? $item->purchase_price), "items.$i.unit_cost");
                if ($item->source_unit_cost !== null && !$sourceCost->isEqualTo($item->source_unit_cost)) {
                    throw ValidationException::withMessages(["items.$i.unit_cost"=>__('The receipt cost must match the confirmed purchase line. Amend before the first receipt or record a traced correction.')]);
                }
                $baseCost = MoneyDecimal::rounded($sourceCost->dividedBy((string)$factor, 6, RoundingMode::HalfUp));
                $lineAmount = MoneyDecimal::rounded($sourceCost->multipliedBy($qty));
                $expiryStatus = $line['expiry_status'] ?? 'unknown';
                $expiresOn = $line['expires_on'] ?? null;
                $receiptItem = PurchaseReceiptItem::create([
                    'purchase_receipt_id' => $receipt->id, 'purchase_item_id' => $item->id, 'product_id' => $item->product_id,
                    'product_batch_id' => null, 'product_unit_id' => $item->product_unit_id,
                    'unit_id_snapshot' => $item->unit_id_snapshot, 'unit_label_snapshot' => $item->unit_label_snapshot,
                    'factor_used' => (string)$factor, 'entered_quantity' => (string)$qty, 'base_quantity' => (string)$base,
                    'source_unit_cost' => (string)$sourceCost, 'unit_cost' => (string)$baseCost, 'source_line_amount' => (string)$lineAmount,
                    'source_line_amount_exact' => (string)$sourceCost->multipliedBy($qty),
                    'allows_fractional_snapshot' => (bool)$fractionalRule,
                    'expiry_status' => $expiryStatus, 'expires_on' => $expiresOn,
                ]);
                $movement = app(StockService::class)->increase((int)$shop->id, (int)$item->product_id, (string)$base, [
                    'correlation_key' => 'purchase-receipt:'.$receipt->id, 'correlation_line' => $i + 1, 'user_id' => $userId,
                    'purchase_receipt_item_id' => $receiptItem->id,
                    'batch' => ['expiry_status' => $expiryStatus, 'expires_on' => $expiresOn, 'received_at' => $receipt->received_at,
                        'purchase_receipt_item_id'=>$receiptItem->id, 'unit_cost' => (string)$baseCost, 'provenance' => 'purchase','currency_code'=>$purchase->currency_code],
                ]);
                $receiptItem->forceFill(['product_batch_id'=>$movement->product_batch_id])->save();
                if (!$item->product_batch_id) $item->forceFill(['product_batch_id' => $movement->product_batch_id])->save();
            }
            $this->refreshReceiptStatus($purchase);
            return $receipt->load('items');
        }, 3);
    }

    public function paySupplier(Purchase $purchase, array $data, PointOfSale $shop, int $userId): Payment
    {
        return DB::transaction(function () use ($purchase, $data, $shop, $userId) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if (!$purchase->currency_code || !in_array($purchase->payment_status, ['unpaid','partial','paid'], true) || $purchase->cancelled_at)
                throw ValidationException::withMessages(['purchase' => __('The historical or cancelled purchase balance is not available for payment.')]);
            if (($data['currency_code'] ?? $purchase->currency_code) !== $purchase->currency_code)
                throw ValidationException::withMessages(['currency_code' => __('The payment currency must match the purchase currency.')]);
            if (!$purchase->point_of_sale_id || (int)$purchase->point_of_sale_id !== (int)$shop->id)
                throw ValidationException::withMessages(['purchase' => __('The purchase belongs to a different shop.')]);
            $amount = MoneyDecimal::parse($data['amount'], 'amount');
            if ($amount->isLessThanOrEqualTo('0')) throw ValidationException::withMessages(['amount' => __('The payment amount must be positive.')]);
            $hash = hash('sha256', json_encode(['purchase_id'=>$purchase->id,'shop_id'=>$shop->id,'user_id'=>$userId,'data'=>$data], JSON_THROW_ON_ERROR));
            $existing = Payment::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if (!hash_equals($existing->request_hash, $hash)) throw ValidationException::withMessages(['idempotency_key' => __('This payment key was already used with different data.')]);
                return $existing->load('allocations');
            }
            $allocated = $this->netPaid($purchase);
            if ($allocated->plus($amount)->isGreaterThan((string)$purchase->grand_total))
                throw ValidationException::withMessages(['amount' => __('The payment exceeds the outstanding purchase balance.')]);
            $payment = Payment::create(['point_of_sale_id' => $shop->id, 'user_id' => $userId, 'supplier_id' => $purchase->supplier_id,
                'direction' => 'outgoing', 'method' => $data['method'], 'source_amount' => (string)$amount,
                'received_amount' => (string)$amount, 'change_amount' => '0.000000', 'net_amount' => (string)$amount,
                'currency_code' => $purchase->currency_code, 'external_reference' => $data['external_reference'] ?? null,
                'idempotency_key' => $data['idempotency_key'], 'request_hash' => $hash, 'status' => 'posted',
                'provenance' => 'live', 'occurred_at' => now('Africa/Douala')]);
            PaymentAllocation::create(['payment_id' => $payment->id, 'purchase_id' => $purchase->id, 'amount' => (string)$amount, 'provenance' => 'live']);
            $total = $allocated->plus($amount);
            $purchase->forceFill(['payment_status' => $total->isEqualTo((string)$purchase->grand_total) ? 'paid' : 'partial'])->save();
            return $payment->load('allocations');
        }, 3);
    }

    private function refreshReceiptStatus(Purchase $purchase): void
    {
        $items = PurchaseItem::query()->where('purchase_id', $purchase->id)->lockForUpdate()->get();
        $complete = true; $started = false;
        foreach ($items as $item) {
            $received = BigDecimal::zero();
            foreach (PurchaseReceiptItem::query()->where('purchase_item_id', $item->id)->get(['entered_quantity']) as $row) $received = $received->plus($row->entered_quantity);
            $ordered = BigDecimal::of((string)($item->entered_quantity ?? $item->quantity));
            if ($received->isZero()) $complete = false;
            else { $started = true; if (!$received->isEqualTo($ordered)) $complete = false; }
        }
        $purchase->forceFill(['receipt_status' => $complete ? 'received' : ($started ? 'partial' : 'pending')])->save();
    }

    public function reversePayment(Purchase $purchase, Payment $source, array $data, PointOfSale $shop, int $userId): Payment
    {
        return DB::transaction(function () use ($purchase, $source, $data, $shop, $userId) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            abort_unless((int)$purchase->point_of_sale_id === (int)$shop->id, 404);
            $source = Payment::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $allocations = $source->allocations()->get();
            if ($source->direction !== 'outgoing' || $source->reversal_of_id || $allocations->count() !== 1
                || (int)$source->point_of_sale_id !== (int)$shop->id || (int)$source->supplier_id !== (int)$purchase->supplier_id
                || $source->currency_code !== $purchase->currency_code || (int)$allocations->first()->purchase_id !== (int)$purchase->id)
                throw ValidationException::withMessages(['payment'=>__('Only a payment allocated to this purchase may be reversed.')]);
            $existing = Payment::where('reversal_of_id', $source->id)->first();
            if ($existing) {
                if ($existing->reason !== $data['reason'] || $existing->method !== $data['method'] || $existing->external_reference !== ($data['external_reference'] ?? null))
                    throw ValidationException::withMessages(['payment'=>__('This payment was already reversed with different data.')]);
                return $existing;
            }
            $amount = MoneyDecimal::parse((string)$source->net_amount);
            $payment = Payment::create([
                'point_of_sale_id'=>$shop->id, 'user_id'=>$userId, 'supplier_id'=>$purchase->supplier_id,
                'direction'=>'incoming', 'method'=>$data['method'], 'source_amount'=>(string)$amount,
                'received_amount'=>(string)$amount, 'change_amount'=>'0.000000', 'net_amount'=>(string)$amount,
                'currency_code'=>$source->currency_code, 'external_reference'=>$data['external_reference'] ?? null,
                'idempotency_key'=>'payment-reversal:'.$source->id,
                'request_hash'=>hash('sha256', json_encode(['purchase'=>$purchase->id,'source'=>$source->id,'data'=>$data], JSON_THROW_ON_ERROR)),
                'status'=>'posted', 'provenance'=>'payment_reversal', 'reversal_of_id'=>$source->id,
                'reason'=>$data['reason'], 'occurred_at'=>now('Africa/Douala'),
            ]);
            PaymentAllocation::create(['payment_id'=>$payment->id,'purchase_id'=>$purchase->id,'amount'=>(string)$amount,'provenance'=>'payment_reversal']);
            $net = $this->netPaid($purchase);
            $purchase->forceFill(['payment_status'=>$net->isZero() ? 'unpaid' : ($net->isEqualTo((string)$purchase->grand_total) ? 'paid' : 'partial')])->save();
            return $payment->load('allocations');
        }, 3);
    }

    public function cancel(Purchase $purchase, string $reason, PointOfSale $shop, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $reason, $shop, $userId) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            abort_unless((int)$purchase->point_of_sale_id === (int)$shop->id, 404);
            if ($purchase->cancelled_at) {
                if ($purchase->cancellation_reason !== $reason) throw ValidationException::withMessages(['reason'=>__('This purchase was already cancelled with a different reason.')]);
                return $purchase;
            }
            if (!$purchase->currency_code || $purchase->receipt_status === 'unknown') throw ValidationException::withMessages(['purchase'=>__('Historical purchases cannot be cancelled automatically.')]);
            if (!$this->netPaid($purchase)->isZero()) throw ValidationException::withMessages(['purchase'=>__('Record the supplier payment reversal before cancelling this purchase.')]);
            $productIds = $purchase->items()->pluck('product_id')->unique()->sort()->values();
            Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
            $receiptItemIds = PurchaseReceiptItem::whereIn('purchase_item_id', $purchase->items()->pluck('id'))->pluck('id');
            $sources = \App\Models\StockMovement::whereIn('purchase_receipt_item_id', $receiptItemIds)->where('type','receipt')->orderBy('product_id')->orderBy('id')->get();
            if ($sources->count() !== $receiptItemIds->count()) throw ValidationException::withMessages(['purchase'=>__('The purchase receipts do not reconcile with the stock journal.')]);
            foreach ($sources as $source) app(StockService::class)->reverseReceipt((int)$shop->id, (int)$source->id, $reason, 'purchase-cancel:'.$purchase->id.':'.$source->id, $userId);
            $purchase->receipts()->update(['status'=>'cancelled']);
            $purchase->forceFill(['cancelled_at'=>now('Africa/Douala'),'cancelled_by'=>$userId,'cancellation_reason'=>$reason,'receipt_status'=>'cancelled','payment_status'=>'cancelled'])->save();
            return $purchase;
        }, 3);
    }

    private function netPaid(Purchase $purchase): BigDecimal
    {
        $paid = BigDecimal::zero();
        foreach (PaymentAllocation::where('purchase_id', $purchase->id)->with('payment')->lockForUpdate()->get() as $allocation) {
            $amount = BigDecimal::of($allocation->amount);
            $paid = $allocation->payment->direction === 'outgoing' ? $paid->plus($amount) : $paid->minus($amount);
        }
        return $paid;
    }
}

<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\MoneyDecimal;
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Compatibility adapter for catalogue imports; all receipts use PurchaseService. */
final class ReceiptStockService
{
    public function receive(PurchaseItem $item, int $shopId, int $userId, array $expiry = []): void
    {
        DB::transaction(function () use ($item, $shopId, $userId, $expiry) {
            $purchase = Purchase::whereKey($item->purchase_id)->lockForUpdate()->firstOrFail();
            $item = PurchaseItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($purchase->receipt_status === 'unknown' || ($item->product_batch_id && !$item->receipts()->exists())) {
                throw ValidationException::withMessages(['purchase'=>__('Historical receipts cannot be replayed.')]);
            }
            if ($item->entered_quantity === null) {
                $unit = ProductUnit::where('product_id',$item->product_id)->where('is_reference',true)->where('is_active',true)->first();
                if (!$unit || $item->product?->allows_fractional === null) {
                    throw ValidationException::withMessages(['quantity'=>__('Configure an active purchase packaging first.')]);
                }
                $quantity = QuantityDecimal::parse((string)$item->quantity, 'quantity', true);
                $sourceCost = MoneyDecimal::parse((string)$item->purchase_price, 'unit_cost');
                $exact = $sourceCost->multipliedBy($quantity);
                $item->forceFill([
                    'product_unit_id'=>$unit->id, 'unit_id_snapshot'=>$unit->unit_id, 'unit_label_snapshot'=>$unit->label,
                    'factor_used'=>$unit->factor, 'entered_quantity'=>(string)$quantity,
                    'base_quantity'=>QuantityDecimal::toBase((string)$quantity, (string)$unit->factor, (bool)$item->product->allows_fractional),
                    'source_unit_cost'=>(string)$sourceCost, 'source_line_amount'=>(string)MoneyDecimal::rounded($exact),
                    'source_line_amount_exact'=>(string)$exact, 'allows_fractional_snapshot'=>(bool)$item->product->allows_fractional,
                ])->save();
            }
            app(PurchaseService::class)->receive($purchase, [[
                'purchase_item_id'=>$item->id, 'quantity'=>(string)$item->entered_quantity,
                'unit_cost'=>(string)$item->source_unit_cost,
                'expiry_status'=>$expiry['expiry_status'] ?? 'unknown', 'expires_on'=>$expiry['expires_on'] ?? null,
            ]], PointOfSale::findOrFail($shopId), $userId, 'purchase:'.$purchase->id.':import:'.$item->id);
        }, 3);
    }
}

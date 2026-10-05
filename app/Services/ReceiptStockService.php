<?php

namespace App\Services;

use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

/** Adapter for existing receipts until PurchaseService handles packaging in 3.D. */
final class ReceiptStockService
{
    public function receive(PurchaseItem $item, int $shopId, int $userId, array $expiry = []): void
    {
        DB::transaction(function () use ($item,$shopId,$userId,$expiry) {
            $movement = app(StockService::class)->increase($shopId, (int)$item->product_id, (string)$item->quantity, [
                'correlation_key'=>'purchase:item:'.$item->id,
                'user_id'=>$userId,
                'batch'=>[
                    'expiry_status'=>$expiry['expiry_status'] ?? 'unknown',
                    'expires_on'=>$expiry['expires_on'] ?? null,
                    'received_at'=>now('Africa/Douala'),
                    'unit_cost'=>(string)$item->purchase_price,
                    'provenance'=>'purchase',
                ],
            ]);
            $item->update(['product_batch_id'=>$movement->product_batch_id]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class StockMovement extends Model
{
    protected $fillable = [
        'point_of_sale_id', 'product_id', 'product_batch_id', 'bucket', 'quantity_delta',
        'type', 'occurred_at', 'user_id', 'unit_cost', 'correlation_key', 'correlation_line',
        'order_product_id', 'purchase_receipt_item_id', 'conversion_run_id', 'reversal_of_id', 'reason',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'occurred_at' => \App\Casts\StockDateTime::class,
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stock movements are immutable; create a compensating movement.'));
        static::deleting(fn () => throw new LogicException('Stock movements are immutable; create a compensating movement.'));
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function reversedMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function orderAllocation(): HasOne
    {
        return $this->hasOne(OrderStockAllocation::class, 'stock_movement_id');
    }
}

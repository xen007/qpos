<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id', 'batch_number', 'expiry_status', 'expires_on', 'received_at', 'unit_cost',
        'purchase_receipt_item_id', 'provenance',
    ];

    protected $casts = [
        'expires_on' => 'date',
        'received_at' => \App\Casts\StockDateTime::class,
        'unit_cost' => 'decimal:6',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BatchStock::class);
    }
}

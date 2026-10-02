<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = ['unit_id', 'code', 'label', 'factor', 'is_reference', 'is_active', 'sale_price_ttc', 'reference_purchase_cost'];
    protected $casts = ['factor' => 'decimal:6', 'sale_price_ttc' => 'decimal:6', 'reference_purchase_cost' => 'decimal:6', 'is_reference' => 'boolean', 'is_active' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }
}

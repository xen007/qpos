<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $table = 'product_stock';

    protected $fillable = [
        'point_of_sale_id', 'product_id', 'saleable_quantity', 'unsaleable_quantity',
        'in_transit_quantity', 'unallocated_opening_quantity',
    ];

    protected $casts = [
        'saleable_quantity' => 'decimal:6',
        'unsaleable_quantity' => 'decimal:6',
        'in_transit_quantity' => 'decimal:6',
        'unallocated_opening_quantity' => 'decimal:6',
    ];

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchStock extends Model
{
    protected $table = 'batch_stock';

    protected $fillable = ['point_of_sale_id', 'product_batch_id', 'saleable_quantity', 'unsaleable_quantity'];

    protected $casts = [
        'saleable_quantity' => 'decimal:6',
        'unsaleable_quantity' => 'decimal:6',
    ];

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}

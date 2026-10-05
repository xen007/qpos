<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_batch_id',
        'purchase_id',
        'product_id',
        'purchase_price',
        'price',
        'discount_value',
        'discount_type',
        'quantity',
    ];
    protected $appends = ['name', 'stock'];
    private bool $stockLoaded = false;
    private ?string $stockValue = null;

    public function withStockValue(?string $value): static
    {
        $this->stockLoaded = true;
        $this->stockValue = $value;
        return $this;
    }
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    // Accessor for Product Name
    public function getNameAttribute()
    {
        return $this->product ? $this->product->name : null;
    }

    // Accessor for Current Stock Quantity
    public function getStockAttribute()
    {
        if ($this->stockLoaded) { return $this->stockValue; }
        $shopId = $this->purchase?->point_of_sale_id;
        return $shopId && $this->product_id ? app(\App\Services\StockService::class)->available((int)$shopId,(int)$this->product_id) : null;
    }
}

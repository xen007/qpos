<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_batch_id',
        'product_unit_id',
        'unit_id_snapshot',
        'unit_label_snapshot',
        'factor_used',
        'entered_quantity',
        'base_quantity',
        'source_unit_cost',
        'source_line_amount',
        'source_line_amount_exact',
        'allows_fractional_snapshot',
        'purchase_id',
        'product_id',
        'purchase_price',
        'price',
        'discount_value',
        'discount_type',
        'quantity',
    ];
    protected $appends = ['name', 'stock'];
    protected $casts = ['quantity'=>'decimal:6','purchase_price'=>'decimal:6','price'=>'decimal:6','factor_used'=>'decimal:6','entered_quantity'=>'decimal:6','base_quantity'=>'decimal:6','source_unit_cost'=>'decimal:6','source_line_amount'=>'decimal:6','allows_fractional_snapshot'=>'boolean'];
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
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
    public function receipts()
    {
        return $this->hasMany(PurchaseReceiptItem::class);
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

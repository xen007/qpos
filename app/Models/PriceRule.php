<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PriceRule extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['price_ttc' => 'decimal:6', 'minimum_quantity' => 'decimal:6',
        'priority' => 'integer', 'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    public function productUnit() { return $this->belongsTo(ProductUnit::class); }
    public function pointOfSale() { return $this->belongsTo(PointOfSale::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}

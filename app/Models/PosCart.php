<?php

namespace App\Models;

use App\Casts\SaleDateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCart extends Model
{
    use HasFactory;

    public function freshTimestamp()
    {
        return $this->cart_id !== null ? now('UTC') : parent::freshTimestamp();
    }

    protected $guarded = [];

    protected $casts = ['created_at' => SaleDateTime::class, 'updated_at' => SaleDateTime::class, 'quantity' => 'decimal:6'];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

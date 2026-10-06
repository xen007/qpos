<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceipt extends Model
{
    protected $guarded = [];
    protected $casts = ['received_at' => \App\Casts\StockDateTime::class];
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function items() { return $this->hasMany(PurchaseReceiptItem::class); }
}

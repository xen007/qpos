<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class PurchaseOrderItem extends Model {
 protected $guarded=['id'];
 protected $casts=['snapshot'=>'array','suggestion'=>'array','quantity_ordered'=>'decimal:6','closed_quantity'=>'decimal:6','unit_price'=>'decimal:6','discount_amount'=>'decimal:6'];
 public function product(){return $this->belongsTo(Product::class);}
 public function productUnit(){return $this->belongsTo(ProductUnit::class);}
 public function purchaseItem(){return $this->belongsTo(PurchaseItem::class);}
}

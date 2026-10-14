<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class PurchaseOrder extends Model {
 protected $guarded=['id'];
 protected $casts=['snapshot'=>'array','approved_at'=>\App\Casts\UtcDateTime::class,'issued_at'=>\App\Casts\UtcDateTime::class,'closed_at'=>\App\Casts\UtcDateTime::class,'total_amount'=>'decimal:6'];
 public function items(){return $this->hasMany(PurchaseOrderItem::class);}
 public function supplier(){return $this->belongsTo(Supplier::class);}
 public function shop(){return $this->belongsTo(PointOfSale::class,'point_of_sale_id');}
 public function purchase(){return $this->belongsTo(Purchase::class);}
 public function events(){return $this->hasMany(PurchaseOrderEvent::class);}
}

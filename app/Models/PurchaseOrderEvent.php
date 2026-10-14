<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
final class PurchaseOrderEvent extends Model {
 public $timestamps=false;protected $guarded=['id'];protected $casts=['before_values'=>'array','after_values'=>'array','occurred_at'=>\App\Casts\UtcDateTime::class];
 protected static function booted():void {static::updating(fn()=>throw ValidationException::withMessages(['event'=>__('replenishment.immutable')]));static::deleting(fn()=>throw ValidationException::withMessages(['event'=>__('replenishment.immutable')]));}
}

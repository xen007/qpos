<?php
namespace App\Services;
use App\Models\{Product,ProductUnit,Supplier};
use App\Support\{QuantityDecimal,MoneyDecimal};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Brick\Math\BigDecimal;
final class SupplierCatalogService {
 public function save(array $d,int $actor):object {
  return DB::transaction(function()use($d,$actor){
   $p=Product::whereKey($d['product_id'])->lockForUpdate()->firstOrFail();
   $s=Supplier::whereKey($d['supplier_id'])->lockForUpdate()->firstOrFail();
   if(!$s->is_active || $s->is_internal)throw ValidationException::withMessages(['supplier_id'=>__('replenishment.active_supplier')]);
   $u=ProductUnit::whereKey($d['product_unit_id'])->where('product_id',$p->id)->where('is_active',true)->lockForUpdate()->firstOrFail();
   if($p->allows_fractional===null)throw ValidationException::withMessages(['product_id'=>__('Set the fractional quantity rule on the product first.')]);
   $min=QuantityDecimal::parse((string)$d['minimum_order'],'minimum_order',true);$multiple=QuantityDecimal::parse((string)$d['order_multiple'],'order_multiple',true);
   foreach([$min,$multiple] as $q)QuantityDecimal::toBase((string)$q,$u->factor,$p->allows_fractional);
   if(!$p->allows_fractional && (!$min->dividedBy($multiple,0,\Brick\Math\RoundingMode::Down)->multipliedBy($multiple)->isEqualTo($min)))throw ValidationException::withMessages(['minimum_order'=>__('replenishment.multiple_help')]);
   $price=isset($d['estimated_cost'])?(string)MoneyDecimal::parse((string)$d['estimated_cost'],'estimated_cost'):null;
   if($price!==null && empty($d['price_on']))throw ValidationException::withMessages(['price_on'=>__('replenishment.price_date_required')]);
   $before=DB::table('supplier_catalog')->where('product_id',$p->id)->where('supplier_id',$s->id)->first();
   if($d['preferred']??false)DB::table('supplier_catalog')->where('product_id',$p->id)->update(['preferred'=>false,'updated_at'=>now('UTC')]);
   $values=['supplier_id'=>$s->id,'product_id'=>$p->id,'product_unit_id'=>$u->id,'supplier_reference'=>$d['supplier_reference']??null,'delay_days'=>(int)$d['delay_days'],'minimum_order'=>(string)$min,'order_multiple'=>(string)$multiple,'estimated_cost'=>$price,'price_on'=>$price!==null?$d['price_on']:null,'preferred'=>(bool)($d['preferred']??false),'is_active'=>(bool)($d['is_active']??true),'updated_at'=>now('UTC'),'created_at'=>$before?->created_at??now('UTC')];
   DB::table('supplier_catalog')->updateOrInsert(['supplier_id'=>$s->id,'product_id'=>$p->id],$values);
   app(ReplenishmentAudit::class)->record('supplier.catalog_updated',$p->id,null,(array)$before,$values,$d['reason'],$actor);
   return DB::table('supplier_catalog')->where('supplier_id',$s->id)->where('product_id',$p->id)->first();
  },3);
 }
}

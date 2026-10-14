<?php
namespace App\Support;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{DB,Schema};
use Brick\Math\BigDecimal;
final class ReplenishmentFilters {
 public static function products(Builder $q,Request $r,int $shop):void {
  $r->validate(['category_id'=>'nullable|integer|exists:categories,id','supplier_id'=>'nullable|integer|exists:suppliers,id','stock_state'=>'nullable|in:low,out,normal,over']);
  if($r->filled('category_id'))$q->where('products.category_id',$r->category_id);
  if($r->filled('supplier_id')){
   if(!Schema::hasTable('supplier_catalog'))abort(503,__('administration.migration_required'));
   $q->whereExists(fn($p)=>$p->selectRaw('1')->from('supplier_catalog as sc')->whereColumn('sc.product_id','products.id')->where('sc.supplier_id',$r->supplier_id)->where('sc.is_active',true));
  }
  if($r->filled('stock_state')){
   $min=self::valueSql('minimum',$shop,(string)config('system.notification_low_stock_threshold',config('notifications.low_stock_threshold','10')));
   $max=self::valueSql('maximum',$shop,'NULL');$enabled=self::valueSql('maximum_enabled',$shop,'0');
   $state=$r->stock_state;
   $clause=match($state){'out'=>'COALESCE(stock_available,0)=0','low'=>"COALESCE(stock_available,0)>0 AND COALESCE(stock_available,0)<=$min",'over'=>"$enabled=1 AND COALESCE(stock_available,0)>$max",default=>"COALESCE(stock_available,0)>$min AND ($enabled=0 OR $max IS NULL OR COALESCE(stock_available,0)<=$max)"};
   $inner=clone $q->getQuery();
   $q->setQuery($inner->newQuery()->fromSub($inner,'products')->select('products.*')->whereRaw($clause));
  }
 }
 public static function valueSql(string $field,int $shop,string $default):string {
  if(!in_array($field,['minimum','maximum','maximum_enabled'],true))throw new \InvalidArgumentException('Invalid policy field');
  if($default!=='NULL')$default=(string)BigDecimal::of($default);
  if(!Schema::hasTable('stock_policies'))return $default;
  return "(COALESCE((SELECT sp.$field FROM stock_policies sp WHERE (sp.point_of_sale_id=$shop OR sp.point_of_sale_id IS NULL) AND (sp.product_id=products.id OR sp.product_id IS NULL) AND sp.$field IS NOT NULL ORDER BY (sp.product_id IS NOT NULL)*2+(sp.point_of_sale_id IS NOT NULL) DESC LIMIT 1),$default))";
 }
}

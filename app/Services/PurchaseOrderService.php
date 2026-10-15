<?php
namespace App\Services;
use App\Models\{User,PointOfSale,Product,ProductUnit,Supplier,PurchaseOrder,PurchaseOrderItem,PurchaseOrderEvent};
use App\Support\{QuantityDecimal,MoneyDecimal};
use Brick\Math\{BigDecimal,RoundingMode};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class PurchaseOrderService
{
 public function actor(int $actor,int $shop,array $permissions):User {
  $u=User::whereKey($actor)->firstOrFail();
  abort_unless(!$u->is_suspended && $u->hasAllPermissions($permissions) && $u->can('point_of_sale_access') && PointOfSale::accessibleBy($u)->whereKey($shop)->where('is_active',true)->exists(),403);
  abort_unless(!$u->fixed_point_of_sale_id || (int)$u->fixed_point_of_sale_id===$shop,403);
  return $u;
 }
 private function hash(string $action,int $actor,int $shop,?int $id,array $d):string {
  return hash('sha256',json_encode([$action,$actor,$shop,$id,$d],JSON_THROW_ON_ERROR));
 }
 private function replay(string $key,string $hash):?PurchaseOrder {
  $event=PurchaseOrderEvent::where('operation_key',$key)->lockForUpdate()->first();
  if(!$event)return null;
  if(!hash_equals($event->request_hash,$hash))$this->fail('replenishment.key_conflict');
  return PurchaseOrder::findOrFail($event->purchase_order_id)->load('items');
 }
 private function fail(string $message):never {throw ValidationException::withMessages(['purchase_order'=>__($message)]);}
 private function event(PurchaseOrder $po,string $action,int $actor,string $key,string $hash,array $before,string $reason=''):void {
  $after=$po->fresh()->load('items')->toArray();
  PurchaseOrderEvent::create(['purchase_order_id'=>$po->id,'user_id'=>$actor,'action'=>$action,'operation_key'=>$key,'request_hash'=>$hash,'reason'=>$reason,'before_values'=>$before,'after_values'=>$after,'occurred_at'=>now('UTC')]);
  app(ReplenishmentAudit::class)->record('purchase_order.'.$action,$po->id,$po->point_of_sale_id,$before,$after,$reason,$actor);
 }
 public function create(array $d,int $actor,int $shop,?string $generation=null):PurchaseOrder {
  return DB::transaction(function()use($d,$actor,$shop,$generation){
   User::whereKey($actor)->lockForUpdate()->firstOrFail();$this->actor($actor,$shop,['purchase_order_create']);
   $hash=$this->hash('create',$actor,$shop,null,$d);if($prior=$this->replay($d['operation_key'],$hash))return $prior;
   $s=Supplier::whereKey($d['supplier_id'])->lockForUpdate()->firstOrFail();if(!$s->is_active||$s->is_internal)$this->fail('replenishment.active_supplier');
   if($generation && ($prior=PurchaseOrder::where('generation_key',$generation)->lockForUpdate()->first()))return $prior;
   $year=(int)now('Africa/Douala')->format('Y');DB::table('purchase_order_counters')->insertOrIgnore(['year'=>$year,'value'=>0]);
   $counter=DB::table('purchase_order_counters')->where('year',$year)->lockForUpdate()->first();$number=$counter->value+1;DB::table('purchase_order_counters')->where('year',$year)->update(['value'=>$number]);
   $po=PurchaseOrder::create(['reference'=>'BC-'.$year.'-'.str_pad((string)$number,5,'0',STR_PAD_LEFT),'supplier_id'=>$s->id,'point_of_sale_id'=>$shop,'user_id'=>$actor,'mode'=>$d['mode']??'manual','expected_date'=>$d['expected_date']??null,'notes'=>$d['notes']??null,'generation_key'=>$generation]);
   $this->replaceLines($po,$d['items']??[]);$this->event($po,'created',$actor,$d['operation_key'],$hash,[]);
   return $po->fresh()->load('items');
  },3);
 }
 public function update(int $id,array $d,int $actor,int $shop):PurchaseOrder {
  return DB::transaction(function()use($id,$d,$actor,$shop){
   User::whereKey($actor)->lockForUpdate()->firstOrFail();$this->actor($actor,$shop,['purchase_order_update']);
   $po=PurchaseOrder::whereKey($id)->where('point_of_sale_id',$shop)->lockForUpdate()->firstOrFail();
   $hash=$this->hash('update',$actor,$shop,$id,$d);if($prior=$this->replay($d['operation_key'],$hash))return $prior;
   if($po->status!=='draft'||$po->version!==(int)$d['version'])$this->fail('replenishment.stale_draft');
   $before=$po->load('items')->toArray();
   $po->forceFill(['expected_date'=>$d['expected_date']??null,'notes'=>$d['notes']??null,'version'=>$po->version+1])->save();
   $this->replaceLines($po,$d['items']);$this->event($po,'updated',$actor,$d['operation_key'],$hash,$before,$d['reason']);
   return $po->fresh()->load('items');
  },3);
 }
 private function replaceLines(PurchaseOrder $po,array $lines):void {
  if(count($lines)>200)$this->fail('replenishment.line_limit');
  $products=Product::whereIn('id',array_column($lines,'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
  $previous=$po->items()->pluck('suggestion','product_unit_id')->all();
  $seen=[];$prepared=[];$total=BigDecimal::zero();$known=true;
  foreach($lines as $i=>$l){
   $p=$products->get($l['product_id']);if(!$p||!$p->status||$p->allows_fractional===null)$this->fail('replenishment.product_unavailable');
   $u=ProductUnit::whereKey($l['product_unit_id'])->where('product_id',$p->id)->where('is_active',true)->lockForUpdate()->firstOrFail();
   if(isset($seen[$u->id]))$this->fail('replenishment.duplicate_line');$seen[$u->id]=true;
   $qty=QuantityDecimal::parse((string)$l['quantity_ordered'],"items.$i.quantity_ordered",true);QuantityDecimal::toBase((string)$qty,$u->factor,$p->allows_fractional);
   $price=isset($l['unit_price'])?(string)MoneyDecimal::parse((string)$l['unit_price'],"items.$i.unit_price"):null;
   $discount=MoneyDecimal::parse((string)($l['discount_amount']??'0'),"items.$i.discount_amount");
   if($price===null && $discount->isPositive())$this->fail('replenishment.price_required');
   if($price!==null){$amount=BigDecimal::of($price)->multipliedBy($qty)->minus($discount);if($amount->isNegative())$this->fail('replenishment.discount_invalid');$total=$total->plus($amount);}else $known=false;
   $prepared[]=['product_id'=>$p->id,'product_unit_id'=>$u->id,'quantity_ordered'=>(string)$qty,'unit_price'=>$price,'discount_amount'=>(string)$discount,'suggestion'=>$l['suggestion']??($previous[$u->id]??null),
   'snapshot'=>['name'=>$p->name,'sku'=>$p->sku,'unit_label'=>$u->label,'factor'=>$u->factor,'allows_fractional'=>$p->allows_fractional,'sale_price'=>$u->sale_price_ttc,'supplier_reference'=>DB::table('supplier_catalog')->where('product_id',$p->id)->where('supplier_id',$po->supplier_id)->value('supplier_reference')]];
  }
  $po->items()->delete();foreach($prepared as $line)$po->items()->create($line);
  $po->forceFill(['total_amount'=>$known?(string)MoneyDecimal::rounded($total):null])->save();
 }
 private function confirmWarnings(PurchaseOrder $po,int $shop,array $data):void {
  $quantities=[];$warning=false;
  foreach($po->items as $line){
   $quantities[$line->product_id]=($quantities[$line->product_id]??BigDecimal::zero())->plus(BigDecimal::of($line->quantity_ordered)->multipliedBy($line->snapshot['factor']));
   if(array_intersect($line->suggestion['warnings']??[],['exceeds_maximum','expiry_risk']))$warning=true;
  }
  $stock=app(StockAvailability::class)->attach(Product::whereIn('products.id',array_keys($quantities)),$shop)->get()->keyBy('id');
  $policies=DB::table('stock_policies')->where(fn($q)=>$q->whereNull('point_of_sale_id')->orWhere('point_of_sale_id',$shop))
   ->where(fn($q)=>$q->whereNull('product_id')->orWhereIn('product_id',array_keys($quantities)))->get()->map(fn($r)=>(array)$r)->all();
  foreach($quantities as $id=>$quantity){
   $policy=app(StockPolicyService::class)->effective($shop,$id,$policies);
   if($policy['maximum_enabled']&&BigDecimal::of($stock[$id]->getRawOriginal('stock_available')??'0')->plus($quantity)->isGreaterThan($policy['maximum']))$warning=true;
  }
  if($warning&&empty($data['accept_warnings']))$this->fail('replenishment.confirm_warnings');
 }
 public function action(int $id,string $action,array $d,int $actor,int $shop):PurchaseOrder {
  $permission=match($action){'approve'=>'purchase_order_approve','issue','confirm'=>'purchase_order_send','delete'=>'purchase_order_delete',default=>throw new \InvalidArgumentException('Unknown action')};
  return DB::transaction(function()use($id,$action,$d,$actor,$shop,$permission){
   User::whereKey($actor)->lockForUpdate()->firstOrFail();$this->actor($actor,$shop,[$permission]);
   $po=PurchaseOrder::whereKey($id)->where('point_of_sale_id',$shop)->lockForUpdate()->firstOrFail();$po->load('items');
   $hash=$this->hash($action,$actor,$shop,$id,$d);if($prior=$this->replay($d['operation_key'],$hash))return $prior;
   if(isset($d['version'])&&$po->version!==(int)$d['version'])$this->fail('replenishment.stale_draft');
   $before=$po->toArray();$supplier=Supplier::findOrFail($po->supplier_id);if(!$supplier->is_active||$supplier->is_internal)$this->fail('replenishment.active_supplier');
   if($action==='delete'){if($po->status!=='draft')$this->fail('replenishment.draft_only');$po->status='deleted';}
   elseif($action==='approve'){
    $this->actor($actor,$shop,['purchase_create']);
    if($po->status!=='draft'||$po->items->isEmpty()||$po->total_amount===null)$this->fail('replenishment.price_required');
    $data=[];foreach($po->items as $line){
     $p=Product::whereKey($line->product_id)->lockForUpdate()->firstOrFail();$u=ProductUnit::whereKey($line->product_unit_id)->where('is_active',true)->lockForUpdate()->firstOrFail();
     if(!$p->status||$p->allows_fractional===null||$u->product_id!==$p->id)$this->fail('replenishment.product_unavailable');
     if((string)$u->factor!==(string)$line->snapshot['factor']||(bool)$p->allows_fractional!==(bool)$line->snapshot['allows_fractional'])$this->fail('replenishment.packaging_changed');
     // Net source cost may have additional digits; PurchaseService preserves its exact source.
     $net=BigDecimal::of($line->unit_price)->minus(BigDecimal::of($line->discount_amount)->dividedBy($line->quantity_ordered,12,RoundingMode::HalfUp));
     $data[]=['product_id'=>$p->id,'product_unit_id'=>$u->id,'quantity'=>$line->quantity_ordered,'received_quantity'=>'0','unit_cost'=>(string)MoneyDecimal::rounded($net),'sale_price'=>$u->sale_price_ttc??$p->price];
    }
    $this->confirmWarnings($po,$shop,$d);
    $purchase=app(PurchaseService::class)->create(['idempotency_key'=>'po-approve-'.$po->id,'date'=>now('Africa/Douala')->toDateString(),'supplier_id'=>$po->supplier_id,'items'=>$data],PointOfSale::findOrFail($shop),$actor);
    $purchaseLines=$purchase->items->values();foreach($po->items->values() as $i=>$line){$line->forceFill(['purchase_item_id'=>$purchaseLines[$i]->id])->save();DB::table('purchase_items')->where('id',$purchaseLines[$i]->id)->update(['purchase_order_item_id'=>$line->id]);}
    $po->purchase_id=$purchase->id;$po->status='approved';$po->approved_at=now('UTC');$po->snapshot=['supplier'=>['id'=>$supplier->id,'name'=>$supplier->name,'address'=>$supplier->address,'phone'=>$supplier->phone],'shop'=>PointOfSale::findOrFail($shop)->only('id','name'),'items'=>$po->items->toArray(),'approved_by'=>$actor,'currency'=>'XAF','total'=>(string)$purchase->grand_total];
    $po->total_amount=$purchase->grand_total;
   }elseif($action==='issue'){if($po->status!=='approved')$this->fail('replenishment.approve_first');$po->status='issued';$po->issued_at=now('UTC');}
   elseif($action==='confirm'){if($po->status!=='issued')$this->fail('replenishment.issue_first');$po->status='confirmed';}
   $po->version++;$po->save();$this->event($po,$action,$actor,$d['operation_key'],$hash,$before,$d['reason']??'');
   return $po->fresh()->load('items');
  },3);
 }
}

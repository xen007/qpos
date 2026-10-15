<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\{PurchaseOrder,PointOfSale,Supplier};
use App\Services\PurchaseOrderService;
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
final class PurchaseOrderController extends Controller {
 private function query(Request $r) {
  $v=$r->validate(['supplier_id'=>'nullable|integer|exists:suppliers,id','shop_id'=>'nullable|integer','category_id'=>'nullable|integer|exists:categories,id','status'=>'nullable|in:draft,approved,issued,confirmed,partial,received,cancelled,closed,deleted','date_from'=>'nullable|date_format:Y-m-d','date_to'=>'nullable|date_format:Y-m-d|after_or_equal:date_from','search'=>'nullable|string|max:100']);
  $shops=PointOfSale::accessibleBy($r->user())->pluck('points_of_sale.id')->all();
  if($r->filled('shop_id')){abort_unless(in_array((int)$v['shop_id'],$shops,true),403);$shops=[(int)$v['shop_id']];}
  $q=PurchaseOrder::with('supplier','shop')->whereIn('point_of_sale_id',$shops);
  if($r->filled('supplier_id'))$q->where('supplier_id',$v['supplier_id']);
  if($r->filled('category_id'))$q->whereHas('items.product',fn($q)=>$q->where('category_id',$v['category_id']));
  if($r->filled('status'))$q->where('status',$v['status']);else $q->where('status','<>','deleted');
  if($r->filled('search'))$q->where('reference','like','%'.$v['search'].'%');
  if($r->filled('date_from'))$q->where('created_at','>=',\Carbon\CarbonImmutable::parse($v['date_from'],'Africa/Douala')->startOfDay()->utc());
  if($r->filled('date_to'))$q->where('created_at','<',\Carbon\CarbonImmutable::parse($v['date_to'],'Africa/Douala')->startOfDay()->addDay()->utc());
  return $q;
 }
 private function find(Request $r,int $id):PurchaseOrder {
  abort_unless(!$r->user()->is_suspended,403);
  return PurchaseOrder::with('items.productUnit','items.product','supplier','shop','events')->whereIn('point_of_sale_id',PointOfSale::accessibleBy($r->user())->select('points_of_sale.id'))->findOrFail($id);
 }
 public function index(Request $r){
  $orders=$this->query($r)->latest('id')->paginate(25)->withQueryString();$suppliers=Supplier::where('is_active',true)->orderBy('name')->get();$shops=PointOfSale::accessibleBy($r->user())->get();
  return view('backend.replenishment.orders',compact('orders','suppliers','shops'));
 }
 public function products(Request $r){return app(\App\Http\Controllers\Backend\Product\ProductController::class)->index($r);}
 public function create(Request $r){
  $shop=StockContext::shop($r);$suppliers=Supplier::where('is_active',true)->where('is_internal',false)->get();$po=null;$key=(string)Str::uuid();
  return view('backend.replenishment.order-form',compact('shop','suppliers','po','key'));
 }
 public function edit(Request $r,int $id){
  $po=$this->find($r,$id);$shop=StockContext::shop($r);abort_unless($po->point_of_sale_id===$shop->id && $po->status==='draft',422);
  $suppliers=Supplier::where('is_active',true)->where('is_internal',false)->get();$key=(string)Str::uuid();
  return view('backend.replenishment.order-form',compact('shop','suppliers','po','key'));
 }
 private function data(Request $r,bool $update=false):array {
  return $r->validate(['operation_key'=>'required|string|min:8|max:96','supplier_id'=>($update?'prohibited':'required').'|integer|exists:suppliers,id','mode'=>'nullable|in:manual,auto,hybrid','version'=>$update?'required|integer|min:1':'prohibited','expected_date'=>'nullable|date_format:Y-m-d','notes'=>'nullable|string|max:3000','reason'=>$update?'required|string|max:500':'nullable|string|max:500','items'=>'required|array|min:1|max:200','items.*.product_id'=>'required|integer|exists:products,id','items.*.product_unit_id'=>'required|integer|distinct|exists:product_units,id','items.*.quantity_ordered'=>'required|string','items.*.unit_price'=>'nullable|string','items.*.discount_amount'=>'nullable|string']);
 }
 public function store(Request $r){
  $po=app(PurchaseOrderService::class)->create($this->data($r),$r->user()->id,StockContext::shop($r)->id);
  return $r->expectsJson()?response()->json(['order'=>$po],201):to_route('backend.admin.purchase-orders.show',$po->id)->with('success',__('replenishment.saved'));
 }
 public function update(Request $r,int $id){
  $po=app(PurchaseOrderService::class)->update($id,$this->data($r,true),$r->user()->id,StockContext::shop($r)->id);
  return $r->expectsJson()?response()->json(['order'=>$po]):to_route('backend.admin.purchase-orders.show',$po->id)->with('success',__('replenishment.saved'));
 }
 public function show(Request $r,int $id){
  $po=$this->find($r,$id);$key=(string)Str::uuid();
  $quantities=app(\App\Services\PurchaseOrderFulfilmentService::class)->quantities($po);
  return view('backend.replenishment.order',compact('po','key','quantities'));
 }
 public function action(Request $r,int $id,string $action){
  $d=$r->validate(['operation_key'=>'required|string|min:8|max:96','version'=>'required|integer|min:1','reason'=>'required|string|max:500','accept_warnings'=>'nullable|boolean']);
  $po=in_array($action,['close','cancel'],true)
   ?app(\App\Services\PurchaseOrderFulfilmentService::class)->finish($id,$action,$d,$r->user()->id,StockContext::shop($r)->id)
   :app(PurchaseOrderService::class)->action($id,$action,$d,$r->user()->id,StockContext::shop($r)->id);
  return $r->expectsJson()?response()->json(['order'=>$po]):to_route('backend.admin.purchase-orders.show',$po->id)->with('success',__('replenishment.saved'));
 }
 public function receive(Request $r,int $id){
  $d=$r->validate(['operation_key'=>'required|string|min:8|max:96','version'=>'required|integer|min:1',
   'reason'=>'nullable|string|max:500','accept_surplus'=>'nullable|boolean','items'=>'required|array|min:1|max:200',
   'items.*.purchase_order_item_id'=>'required|integer|distinct','items.*.quantity'=>'required|string',
   'items.*.expiry_status'=>'required|in:unknown,not_applicable,dated',
   'items.*.expires_on'=>'nullable|date_format:Y-m-d','items.*.batch_number'=>'nullable|string|max:100']);
  $po=app(\App\Services\PurchaseOrderFulfilmentService::class)->receive($id,$d,$r->user()->id,StockContext::shop($r)->id);
  return $r->expectsJson()?response()->json(['order'=>$po]):to_route('backend.admin.purchase-orders.show',$po->id)->with('success',__('replenishment.saved'));
 }
 public function detailExport(Request $r,int $id,string $format){return $this->export($r,$format,$id);}
 private function pdfLogo(): ?string {
  $logo=(string)config('system.site_logo');
  if(!str_starts_with($logo,'/assets/images/'))return null;
  $root=realpath(public_path('assets/images'));$path=realpath(public_path(ltrim($logo,'/')));
  if(!$root||!$path||!str_starts_with($path,$root.DIRECTORY_SEPARATOR)||!is_file($path)||filesize($path)>2097152)return null;
  $info=@getimagesize($path);$mime=$info['mime']??null;
  if(!in_array($mime,['image/png','image/jpeg','image/gif'],true))return null;
  return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
 }
 public function print(Request $r,int $id){
  $orders=collect([$this->find($r,$id)]);$logo=$this->pdfLogo();$printable=true;
  return view('backend.replenishment.orders-pdf',compact('orders','logo','printable'));
 }
 public function export(Request $r,string $format,?int $id=null){
  abort_unless(in_array($format,['pdf','xlsx'],true),404);
   $cap=$format==='pdf'?200:5000;
   $orders=$id?collect([$this->find($r,$id)]):$this->query($r)->withCount('items')->orderBy('id')->limit($cap+1)->get();
   abort_if($orders->count()>$cap,422,__($format==='pdf'?'replenishment.pdf_limit':'replenishment.export_limit'));
   if($format==='pdf'){
    abort_if(($id?$orders->first()->items->count():$orders->sum('items_count'))>1000,422,__('replenishment.pdf_limit'));
    $orders->each(fn($order)=>$order->loadMissing('items'));
    $logo=$this->pdfLogo();
    return Pdf::loadView('backend.replenishment.orders-pdf',compact('orders','logo'))->setPaper('a4')->download('bons-commande.pdf');
   }
  $rows=[];foreach($orders as $po){if($id)foreach($po->items as $l)$rows[]=[$po->reference,$l->snapshot['name']??'',$l->snapshot['unit_label']??'',$l->quantity_ordered,$l->unit_price??'', $l->discount_amount,'XAF'];else $rows[]=[$po->reference,$po->shop->name,$po->supplier->name,$po->status,$po->expected_date??'',$po->total_amount??'','XAF'];}
  return Excel::download(new \App\Exports\PurchaseOrdersExport($rows,$id?[__('Reference'),__('Product'),__('Packaging'),__('Quantity'),__('Price'),__('Discount'),'Currency']:[__('Reference'),__('Shop'),__('Supplier'),__('Status'),__('Date'),__('Total'),'Currency']),'bons-commande.xlsx');
 }
}

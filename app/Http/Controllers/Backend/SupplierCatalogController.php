<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\{Product,Supplier};
use App\Services\SupplierCatalogService;
use App\Support\RoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class SupplierCatalogController extends Controller {
 public function index(Request $r) {
  abort_unless(RoleAssignment::globalManager($r->user()),403);
  $r->validate(['product_id'=>'nullable|integer|exists:products,id','supplier_id'=>'nullable|integer|exists:suppliers,id','search'=>'nullable|string|max:100']);
  $product=$r->filled('product_id')?Product::with('productUnits')->findOrFail($r->product_id):null;
  $products=Product::orderBy('name')->when($r->filled('search'),fn($q)=>$q->where('name','like','%'.$r->search.'%'))->paginate(25)->withQueryString();
  $suppliers=Supplier::where('is_active',true)->where('is_internal',false)->orderBy('name')->get();
  $entries=DB::table('supplier_catalog as c')->join('suppliers as s','s.id','=','c.supplier_id')->join('products as p','p.id','=','c.product_id')->join('product_units as u','u.id','=','c.product_unit_id')
   ->when($r->filled('supplier_id'),fn($q)=>$q->where('c.supplier_id',$r->supplier_id))->when($product,fn($q)=>$q->where('c.product_id',$product->id))->select('c.*','s.name as supplier','p.name as product','u.label as packaging')->orderBy('p.name')->paginate(25,['*'],'catalog_page')->withQueryString();
  return view('backend.replenishment.suppliers',compact('products','product','suppliers','entries'));
 }
 public function store(Request $r) {
  abort_unless(RoleAssignment::globalManager($r->user()),403);
  $d=$r->validate(['product_id'=>'required|integer|exists:products,id','supplier_id'=>'required|integer|exists:suppliers,id','product_unit_id'=>'required|integer|exists:product_units,id','supplier_reference'=>'nullable|string|max:100','delay_days'=>'required|integer|min:0|max:365','minimum_order'=>'required|string','order_multiple'=>'required|string','estimated_cost'=>'nullable|string','price_on'=>'nullable|date_format:Y-m-d|before_or_equal:today','preferred'=>'nullable|boolean','is_active'=>'required|boolean','reason'=>'required|string|max:500']);
  app(SupplierCatalogService::class)->save($d,$r->user()->id);
  return back()->with('success',__('replenishment.saved'));
 }
}

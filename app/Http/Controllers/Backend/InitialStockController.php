<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\{InitialStockService,ProductExpiryClass};
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
final class InitialStockController extends Controller
{
    public function index(Request $r,ProductExpiryClass $expiry)
    {
        abort_unless(Schema::hasTable('initial_stock_entries'),503,__('administration.migration_required'));
        $shop=StockContext::shop($r);
        $filters=$r->validate(['search'=>'nullable|string|max:255','product_id'=>'nullable|integer']);
        $products=Product::where('status',true)->whereNotNull('allows_fractional')
            ->when(!empty($filters['search']),fn($q)=>$q->where(fn($q)=>$q->where('name','like','%'.$filters['search'].'%')->orWhere('sku','like','%'.$filters['search'].'%')))
            ->orderBy('name')->paginate(50)->withQueryString();
        $selected=!empty($filters['product_id'])?Product::where('status',true)->whereNotNull('allows_fractional')->findOrFail($filters['product_id']):null;
        $classes=$expiry->forProducts($products->pluck('id')->when($selected,fn($ids)=>$ids->push($selected->id)));
        $entries=DB::table('initial_stock_entries as e')->join('stock_opening_approvals as a','a.id','=','e.approval_id')
            ->join('products as p','p.id','=','e.product_id')->where('e.point_of_sale_id',$shop->id)
            ->select('e.id','e.recorded_at','p.name','a.quantity','a.unit_cost','a.currency_code','a.reason','a.evidence')
            ->orderByDesc('e.id')->paginate(25,['*'],'history')->withQueryString();
        return view('backend.stock.initial-stock',compact('products','selected','classes','entries','shop')+['key'=>(string)Str::uuid()]);
    }
    public function store(Request $r,InitialStockService $service)
    {
        $product=$r->validate(['product_id'=>'required|integer'])['product_id'];
        $service->record(StockContext::shop($r)->id,(int)$product,$r->only(['operation_key','quantity','unit_cost','currency_code','batch_number','expiry_status','expires_on','received_on','reason','evidence']),$r->user()->id);
        return to_route('backend.admin.stock.initial')->with('success',__('Documented startup stock recorded.'));
    }
}

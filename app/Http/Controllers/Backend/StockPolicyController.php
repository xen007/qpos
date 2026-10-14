<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockPolicyService;
use App\Support\{StockContext,RoleAssignment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class StockPolicyController extends Controller
{
    public function index(Request $r)
    {
        $shop=StockContext::shop($r);
        $r->validate(['product_id'=>'nullable|integer|exists:products,id','scope'=>'nullable|in:shop,product,product_shop,global','search'=>'nullable|string|max:100']);
        $scope=$r->scope??'product_shop';
        if(in_array($scope,['global','product'],true)) abort_unless(RoleAssignment::globalManager($r->user()),403);
        $product=$r->filled('product_id')?Product::findOrFail($r->product_id):null;
        $products=Product::orderBy('name')->when($r->filled('search'),fn($q)=>$q->where('name','like','%'.$r->search.'%'))->paginate(25)->withQueryString();
        $key=match($scope){'global'=>'global','shop'=>'s:'.$shop->id,'product'=>'p:'.($product?->id??0),default=>'s:'.$shop->id.':p:'.($product?->id??0)};
        $stored=DB::table('stock_policies')->where('scope_key',$key)->first();
        $effective=app(StockPolicyService::class)->effective($shop->id,$product?->id??0);
        return view('backend.replenishment.policies',compact('shop','scope','product','products','stored','effective'));
    }
    public function store(Request $r)
    {
        $shop=StockContext::shop($r);
        $data=$r->validate(['scope'=>'required|in:shop,product,product_shop,global','product_id'=>'nullable|integer|exists:products,id','minimum'=>'nullable|numeric|min:0','optimal'=>'nullable|numeric|min:0','maximum'=>'nullable|numeric|min:0','alerts_enabled'=>'nullable|boolean','maximum_enabled'=>'nullable|boolean','hide_out_of_stock'=>'nullable|boolean','reason'=>'required|string|max:500']);
        if(in_array($data['scope'],['global','product'],true)) abort_unless(RoleAssignment::globalManager($r->user()),403);
        if(in_array($data['scope'],['product','product_shop'],true)) abort_unless(!empty($data['product_id']),422);
        app(StockPolicyService::class)->save(in_array($data['scope'],['shop','product_shop'],true)?$shop->id:null,in_array($data['scope'],['product','product_shop'],true)?(int)$data['product_id']:null,$data,$r->user()->id,$data['reason']);
        return back()->with('success',__('replenishment.saved'));
    }
}

<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\{Category, Supplier, User};
use App\Services\{PurchaseOrderSuggestionService, ReplenishmentSettings, ReplenishmentPlanner};
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReplenishmentController extends Controller
{
    private function shop(Request $r)
    {
        $shop=StockContext::shop($r);
        app(ReplenishmentSettings::class)->actor($r->user()->id,$shop->id);
        return $shop;
    }

    public function index(Request $r)
    {
        $shop=$this->shop($r);
        $filters=$r->validate(['supplier_id'=>'nullable|integer|exists:suppliers,id','category_id'=>'nullable|integer|exists:categories,id',
            'search'=>'nullable|string|max:100','window_days'=>'nullable|integer|in:30,60,90','after'=>'nullable|integer|min:0']);
        $settings=app(ReplenishmentSettings::class)->get($shop->id);
        $filters['window_days']=(int)($filters['window_days']??$settings['window_days']);
        $ids=app(PurchaseOrderSuggestionService::class)->products($filters)->where('products.id','>',$filters['after']??0)
            ->orderBy('products.id')->limit(51)->pluck('products.id');
        $more=$ids->count()>50;$ids=$ids->take(50)->all();
        $rows=app(PurchaseOrderSuggestionService::class)->getSuggestions($r->user()->id,$shop->id,$ids,$filters);
        $next=$more?max($ids):null;$key=(string)Str::uuid();
        if($r->expectsJson())return response()->json(['data'=>$rows,'next'=>$next,'operation_key'=>$key]);
        $suppliers=Supplier::where('is_active',true)->where('is_internal',false)->orderBy('name')->pluck('name','id')->all();
        $categories=Category::orderBy('name')->pluck('name','id')->all();
        return view('backend.replenishment.suggestions',compact('shop','rows','filters','settings','next','key','suppliers','categories'));
    }

    public function generate(Request $r)
    {
        $shop=$this->shop($r);
        $run=app(ReplenishmentPlanner::class)->manual($shop->id,$r->user()->id,$r->all());
        if($r->expectsJson())return response()->json(['run'=>$run],$run->state==='complete'?200:202);
        return to_route('backend.admin.replenishment.run',['id'=>$run->id,'operation_point_of_sale_id'=>$shop->id])
            ->with($run->state==='complete'?'success':'warning',__($run->state==='complete'?'replenishment.drafts_ready':'replenishment.generation_retry'));
    }

    public function settings(Request $r)
    {
        $shop=$this->shop($r);$settings=app(ReplenishmentSettings::class)->get($shop->id);
        $recipients=$responsibles=[];
        $users=User::where('is_suspended',false)->with('roles.permissions','permissions')->orderBy('name')->limit(501)->get();
        abort_if($users->count()>500,422,__('replenishment.user_options_limit'));
        foreach($users as $user){
            try{app(ReplenishmentSettings::class)->recipient($user->id,$shop->id);$recipients[$user->id]=$user->name;}
            catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){continue;}
            try{app(ReplenishmentSettings::class)->actor($user->id,$shop->id);$responsibles[$user->id]=$user->name;}
            catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){}
        }
        return view('backend.replenishment.planning-settings',compact('shop','settings','recipients','responsibles'));
    }

    public function saveSettings(Request $r)
    {
        $shop=$this->shop($r);app(ReplenishmentSettings::class)->save($shop->id,$r->all(),$r->user()->id);
        return back()->with('success',__('replenishment.saved'));
    }

    public function runs(Request $r)
    {
        $shop=$this->shop($r);
        $runs=DB::table('replenishment_runs')->where('point_of_sale_id',$shop->id)->orderByDesc('id')->paginate(25)->withQueryString();
        $run=null;$items=null;
        return view('backend.replenishment.runs',compact('shop','runs','run','items'));
    }

    public function run(Request $r,int $id)
    {
        $shop=$this->shop($r);
        $run=DB::table('replenishment_runs')->where('point_of_sale_id',$shop->id)->find($id);abort_unless($run,404);
        $items=DB::table('replenishment_run_items as i')->leftJoin('purchase_orders as o','o.id','=','i.purchase_order_id')
            ->where('i.replenishment_run_id',$run->id)->select('i.*','o.reference')->orderBy('i.id')->paginate(50)->withQueryString();
        $runs=null;return view('backend.replenishment.runs',compact('shop','runs','run','items'));
    }
}

<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\PendingSaleService;
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
final class PendingSaleController extends Controller
{
    public function index(Request $r,PendingSaleService $s)
    {
        abort_unless(Schema::hasTable('pending_sales')&&$r->user()->hasAnyPermission(['pending_sale_prepare','pending_sale_collect']),403);$shop=StockContext::shop($r);$s->refresh($shop->id);
        $rows=DB::table('pending_sales as p')->join('users as seller','seller.id','=','p.seller_user_id')->leftJoin('users as cashier','cashier.id','=','p.cashier_user_id')->join('customers as c','c.id','=','p.customer_id')->where('p.point_of_sale_id',$shop->id)
            ->when(!$r->user()->can('pending_sale_collect'),fn($q)=>$q->where('p.seller_user_id',$r->user()->id))->select('p.*','seller.name as seller','cashier.name as cashier','c.name as customer')->orderByDesc('p.id')->paginate(30);
        return view('backend.phase5.pending-sales',compact('rows','shop'));
    }
    public function submit(Request $r,PendingSaleService $s){$v=$r->validate(['operation_key'=>'required|string|max:64','cart_id'=>'required|string|max:64','quote_hash'=>'required|string|size:64','customer_id'=>'required|integer|exists:customers,id','order_discount'=>'nullable|string']);return response()->json(['pending_sale'=>$s->submit($r->user(),StockContext::shop($r)->id,$v)]);}
    public function take(Request $r,int $id,PendingSaleService $s){$s->take($r->user(),StockContext::shop($r)->id,$id);return to_route('backend.admin.cart.index',['pending_sale_id'=>$id,'operation_point_of_sale_id'=>StockContext::shop($r)->id]);}
    public function quote(Request $r,int $id,PendingSaleService $s){return response()->json($s->quote($r->user(),StockContext::shop($r)->id,$id));}
    public function complete(Request $r,int $id,PendingSaleService $s){$v=$r->validate(['operation_key'=>'required|string|max:64','review_hash'=>'required|string|size:64','confirm_changes'=>'nullable|boolean','credit_amount'=>'nullable|string','cash_received'=>'sometimes|required|string','due_date'=>'nullable|date_format:Y-m-d','payments'=>'present|array|max:10','payments.*.method'=>'required|string|max:24','payments.*.amount'=>'required|string','payments.*.external_reference'=>'nullable|string|max:128','confirm_expired_sale'=>'nullable|boolean','expired_sale_reason'=>'nullable|string|max:255']);return response()->json(['order'=>$s->complete($r->user(),StockContext::shop($r)->id,$id,$v)]);}
    public function resolve(Request $r,int $id,PendingSaleService $s){$v=$r->validate(['action'=>'required|in:release,cancel','reason'=>'required|string|max:500']);$s->resolve($r->user(),StockContext::shop($r)->id,$id,$v['action'],$v['reason']);return back()->with('success',__('Pending sale updated'));}
}

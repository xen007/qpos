<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\CashSupervisionService;
use App\Models\PointOfSale;
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Schema,DB};
final class CashSupervisionController extends Controller
{
    public function index(Request $r,CashSupervisionService $s)
    {
        abort_unless(Schema::hasTable('cash_session_supervisions')&&$r->user()->hasRole('Admin'),403);
        $filters=$r->validate(['state'=>'nullable|in:open,closed','shop'=>'nullable|integer','cashier'=>'nullable|integer']);
        $stores=PointOfSale::accessibleBy($r->user())->orderBy('name')->get();
        $shops=$stores->pluck('id')->all();
        if(!empty($filters['shop'])) { abort_unless(in_array((int)$filters['shop'],$shops,true),403); $shops=[(int)$filters['shop']]; }
        $state=$filters['state']??'open';
        $query=$state==='open'?$s->query($shops):DB::table('cash_sessions as s')->join('users as u','u.id','=','s.user_id')
            ->join('points_of_sale as shop','shop.id','=','s.point_of_sale_id')->whereIn('s.point_of_sale_id',$shops)
            ->where('s.state','closed')->select('s.*','u.name as cashier','shop.name as shop');
        if(!empty($filters['cashier'])) $query->where($state==='open'?'user_id':'s.user_id',(int)$filters['cashier']);
        $rows=($state==='open'?$query->orderByDesc('suspected')->orderBy('last_activity'):$query->orderByDesc('s.closed_at'))->paginate(30)->withQueryString();
        $cashiers=DB::table('users')->whereIn('id',DB::table('cash_sessions')->whereIn('point_of_sale_id',$stores->pluck('id'))->select('user_id'))->orderBy('name')->pluck('name','id');
        return view('backend.phase5.cash-supervision',compact('rows','state','stores','cashiers','filters'));
    }
    public function close(Request $r,int $id,CashSupervisionService $s){$v=$r->validate(['operation_key'=>'required|string|max:64','counted_amount'=>'required|string','reason'=>'required|string|max:500']);$s->close($r->user(),StockContext::shop($r)->id,$id,$v);return back()->with('success',__('Cash session closed with supervision'));}
}

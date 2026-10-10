<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\CashSupervisionService;
use App\Models\PointOfSale;
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
final class CashSupervisionController extends Controller
{
    public function index(Request $r,CashSupervisionService $s){abort_unless(Schema::hasTable('cash_session_supervisions')&&$r->user()->hasRole('Admin'),403);$shops=PointOfSale::accessibleBy($r->user())->pluck('id')->all();$rows=$s->query($shops)->orderByDesc('suspected')->orderBy('last_activity')->paginate(30);return view('backend.phase5.cash-supervision',compact('rows'));}
    public function close(Request $r,int $id,CashSupervisionService $s){$v=$r->validate(['operation_key'=>'required|string|max:64','counted_amount'=>'required|string','reason'=>'required|string|max:500']);$s->close($r->user(),StockContext::shop($r)->id,$id,$v);return back()->with('success',__('Cash session closed with supervision'));}
}

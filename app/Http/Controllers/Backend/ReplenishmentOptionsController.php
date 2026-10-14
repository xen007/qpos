<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Models\{Category,Supplier};
use App\Support\StockContext;
use Illuminate\Http\Request;
final class ReplenishmentOptionsController extends Controller {
 public function index(Request $r) {
  StockContext::shop($r);
  return response()->json(['categories'=>Category::where('status',true)->orderBy('name')->get(['id','name']),'suppliers'=>Supplier::where('is_active',true)->where('is_internal',false)->orderBy('name')->get(['id','name'])]);
 }
}

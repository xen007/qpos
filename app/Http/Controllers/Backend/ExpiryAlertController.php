<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpiryAlertController extends Controller
{
    public function index(Request $request)
    {
        $shopId=(int)($request->attributes->get('point_of_sale')?->id ?: 1);
        $rows=DB::table('batch_stock')->join('product_batches','product_batches.id','=','batch_stock.product_batch_id')
            ->join('products','products.id','=','product_batches.product_id')
            ->where('batch_stock.point_of_sale_id',$shopId)->where('batch_stock.saleable_quantity','>',0)
            ->where('product_batches.expiry_status','dated')->orderBy('product_batches.expires_on')
            ->select('products.name','products.sku','product_batches.batch_number','product_batches.expires_on','product_batches.estimated_expiry','batch_stock.saleable_quantity')->get()
            ->map(function($row){
                $days=now('Africa/Douala')->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($row->expires_on)->startOfDay(), false);
                $row->days_remaining=$days;
                $row->alert=$days<0?'expired':($days<=7?'7 days':($days<=30?'30 days':($days<=90?'90 days':null)));
                return $row;
            })->filter(fn($row)=>$row->alert!==null)->values();
        return view('backend.stock.expiry-alerts',compact('rows'));
    }
}

<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\{PointOfSale,Product,ProductStock};
use App\Services\{InventoryService,StockService,StockTransferService};
use App\Support\StockContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOperationController extends Controller
{
    public function transfers(Request $request)
    {
        $shops=PointOfSale::accessibleBy($request->user())->pluck('id');
        $transfers=DB::table('stock_transfers')->where(fn($q)=>$q->whereIn('source_shop_id',$shops)->orWhereIn('destination_shop_id',$shops))->orderByDesc('id')->paginate(25);
        return view('backend.stock.transfers',['transfers'=>$transfers,'shops'=>PointOfSale::where('is_active',true)->orderBy('code')->get(),
            'products'=>Product::whereNotNull('allows_fractional')->orderBy('name')->get(),'key'=>(string)Str::uuid()]);
    }
    public function createTransfer(Request $request,StockTransferService $service)
    {
        $data=$request->validate(['destination_shop_id'=>'required|integer','items'=>'required|array|min:1|max:200',
            'items.*.product_id'=>'required|integer','items.*.quantity'=>'required|string','reason'=>'required|string|max:5000','operation_key'=>'required|string|max:64']);
        $transfer=$service->create(StockContext::shop($request)->id,(int)$data['destination_shop_id'],$data['items'],$data['reason'],$data['operation_key'],$request->user()->id);
        return to_route('backend.admin.stock.transfers.show',$transfer->id);
    }
    public function transfer(Request $request,int $id)
    {
        $transfer=DB::table('stock_transfers')->find($id); abort_unless($transfer,404);
        $shops=PointOfSale::accessibleBy($request->user())->pluck('id')->map(fn($v)=>(int)$v);
        abort_unless($shops->contains((int)$transfer->source_shop_id) || $shops->contains((int)$transfer->destination_shop_id),404);
        $items=DB::table('stock_transfer_items as i')->join('products as p','p.id','=','i.product_id')->where('i.stock_transfer_id',$id)->select('i.*','p.name','p.sku')->get();
        $allocations=DB::table('stock_transfer_allocations as a')->join('stock_transfer_items as i','i.id','=','a.stock_transfer_item_id')
            ->join('products as p','p.id','=','i.product_id')->join('product_batches as b','b.id','=','a.product_batch_id')
            ->where('i.stock_transfer_id',$id)->select('a.*','p.name','b.batch_number','b.expiry_status','b.expires_on','b.unit_cost','b.currency_code')->get();
        $receipts=DB::table('stock_transfer_receipts')->where('stock_transfer_id',$id)->orderBy('id')->get();
        return view('backend.stock.transfer',compact('transfer','items','allocations','receipts')+[
            'key'=>(string)Str::uuid(),'source'=>PointOfSale::find($transfer->source_shop_id),'destination'=>PointOfSale::find($transfer->destination_shop_id),
            'canSource'=>$shops->contains((int)$transfer->source_shop_id),'canDestination'=>$shops->contains((int)$transfer->destination_shop_id),
        ]);
    }
    public function dispatch(Request $request,int $id,StockTransferService $service)
    {
        $service->dispatch($id,$request->user()->id); return back()->with('success',__('Transfer dispatched.'));
    }
    public function settle(Request $request,int $id,StockTransferService $service)
    {
        $data=$request->validate(['kind'=>'required|in:receive,return,loss','lines'=>'required|array|min:1|max:500',
            'lines.*.allocation_id'=>'required|integer','lines.*.quantity'=>'nullable|string','reason'=>'required|string|max:5000','operation_key'=>'required|string|max:64']);
        $lines=array_values(array_filter($data['lines'],fn($row)=>isset($row['quantity']) && trim($row['quantity'])!=='' && !preg_match('/\A0(?:\.0+)?\z/',$row['quantity'])));
        $service->settle($id,$lines,$data['kind'],$data['reason'],$data['operation_key'],$request->user()->id);
        return back()->with('success',__('Transfer settlement recorded.'));
    }
    public function cancelTransfer(Request $request,int $id,StockTransferService $service)
    {
        $data=$request->validate(['reason'=>'required|string|max:5000']);
        $service->cancelDraft($id,$data['reason'],$request->user()->id); return back()->with('success',__('Transfer cancelled.'));
    }
    public function inventories(Request $request)
    {
        $shop=StockContext::shop($request);
        return view('backend.stock.inventories',[
            'inventories'=>DB::table('inventories')->where('point_of_sale_id',$shop->id)->orderByDesc('id')->paginate(25),
            'products'=>Product::whereNotNull('allows_fractional')->orderBy('name')->get(),'key'=>(string)Str::uuid(),
        ]);
    }
    public function createInventory(Request $request,InventoryService $service)
    {
        $data=$request->validate(['product_ids'=>'required|array|min:1|max:2000','product_ids.*'=>'required|integer','reason'=>'required|string|max:5000','operation_key'=>'required|string|max:64']);
        $inventory=$service->create(StockContext::shop($request)->id,$data['product_ids'],$data['reason'],$data['operation_key'],$request->user()->id);
        return to_route('backend.admin.stock.inventories.show',$inventory->id);
    }
    public function inventory(Request $request,int $id)
    {
        $inventory=DB::table('inventories')->where('point_of_sale_id',StockContext::shop($request)->id)->where('id',$id)->firstOrFail();
        $items=DB::table('inventory_items as i')->join('products as p','p.id','=','i.product_id')->where('inventory_id',$id)->select('i.*','p.name','p.sku')->get();
        $batches=DB::table('product_batches')->whereIn('product_id',$items->pluck('product_id'))->get()->keyBy('id');
        return view('backend.stock.inventory',compact('inventory','items','batches'));
    }
    public function count(Request $request,int $id,int $item,InventoryService $service)
    {
        $request->validate(['action'=>'required|in:begin,count','counts'=>'nullable|array','counts.*'=>'required|string']);
        if ($request->input('action')==='begin') $service->beginCount($id,$item,$request->user()->id);
        else $service->recordCount($id,$item,$request->input('counts',[]),$request->user()->id);
        return back()->with('success',__('Count recorded.'));
    }
    public function validateInventory(Request $request,int $id,InventoryService $service)
    {
        $service->validate($id,$request->user()->id); return back()->with('success',__('Inventory validated.'));
    }
    public function cancelInventory(Request $request,int $id,InventoryService $service)
    {
        $service->cancel($id,$request->user()->id); return back()->with('success',__('Inventory cancelled.'));
    }
    public function openings(Request $request)
    {
        $shop=StockContext::shop($request);
        $products=ProductStock::with('product')->where('point_of_sale_id',$shop->id)->where('unallocated_opening_quantity','>',0)->orderBy('product_id')->paginate(25);
        $approvals=DB::table('stock_opening_approvals as a')->join('products as p','p.id','=','a.product_id')
            ->where('a.point_of_sale_id',$shop->id)->select('a.*','p.name')->orderByDesc('a.id')->limit(50)->get();
        return view('backend.stock.openings',compact('products','approvals'));
    }
    public function opening(Request $request,int $product)
    {
        $stock=ProductStock::with('product')->where('point_of_sale_id',StockContext::shop($request)->id)->where('product_id',$product)->where('unallocated_opening_quantity','>',0)->firstOrFail();
        return view('backend.stock.opening',['stock'=>$stock,'key'=>(string)Str::uuid()]);
    }
    public function approveOpening(Request $request,int $product,StockService $service)
    {
        $data=$request->validate([
            'quantity'=>'required|string','unit_cost'=>'required|string','batch_number'=>'required|string|max:255','currency_code'=>'required|in:XAF,BDT',
            'expiry_status'=>'required|in:dated,not_applicable','expires_on'=>'nullable|date_format:Y-m-d','reason'=>'required|string|max:5000',
            'evidence'=>'required|string|max:5000','operation_key'=>'required|string|max:64',
        ]);
        $service->approveOpening(StockContext::shop($request)->id,$product,$data,$request->user()->id);
        return to_route('backend.admin.stock.openings')->with('success',__('Opening partially or fully approved. The remainder stays blocked.'));
    }
}

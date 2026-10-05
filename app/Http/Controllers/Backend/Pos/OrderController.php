<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PosCart;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use App\Support\StockContext;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $orders = \App\Support\StockDocumentAccess::query(Order::query(),$request)
                ->with('customer')
                ->withSum('products as item_quantity_sum', 'quantity')
                ->select('orders.*');
            return DataTables::of($orders)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs cellules
                // (etat, actions) cote page, sans markup Bootstrap.
                ->addColumn('id', fn($data) => $data->id)
                ->addColumn('is_paid', fn($data) => (bool) $data->status)
                ->addColumn('saleId', fn($data) => "#" . $data->id)
                ->addColumn('customer', fn($data) => $data->customer->name ?? '-')
                ->addColumn('item', fn($data) => (int) ($data->item_quantity_sum ?? 0))
                ->addColumn('sub_total', fn($data) => number_format($data->sub_total, 2, '.', ','))
                ->addColumn('discount', fn($data) => number_format($data->discount, 2, '.', ','))
                ->addColumn('total', fn($data) => number_format($data->total, 2, '.', ','))
                ->addColumn('paid', fn($data) => number_format($data->paid, 2, '.', ','))
                ->addColumn('due', fn($data) => number_format($data->due, 2, '.', ','))
                ->addColumn('status', fn($data) => $data->status
                    ? '<span class="badge bg-primary">' . e(__('Paid')) . '</span>'
                    : '<span class="badge bg-danger">' . e(__('Due')) . '</span>')
                ->addColumn('action', function ($data) {
                    $buttons = '';

                    $buttons .= '<a class="btn btn-success btn-sm" href="' . route('backend.admin.orders.invoice', $data->id) . '"><i class="fas fa-file-invoice"></i> ' . e(__('Invoice')) . '</a>';

                    $buttons .= '<a class="btn btn-secondary btn-sm" href="' . route('backend.admin.orders.pos-invoice', $data->id) . '"><i class="fas fa-file-invoice"></i> ' . e(__('POS Receipt')) . '</a>';
                    if (!$data->status && auth()->user()->can('sale_update')) {
                        $buttons .= '<a class="btn btn-warning btn-sm" href="' . route('backend.admin.due.collection', $data->id) . '"><i class="fas fa-receipt"></i> ' . e(__('Collect Due')) . '</a>';
                    }
                    $buttons .= '<a class="btn btn-primary btn-sm" href="' . route('backend.admin.orders.transactions', $data->id) . '"><i class="fas fa-exchange-alt"></i> ' . e(__('Transactions')) . '</a>';
                    return $buttons;
                })
                ->rawColumns(['status', 'action'])
                ->toJson();
        }
        return view('backend.orders.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paid' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $shopId = StockContext::shop($request)->id;
        $order = DB::transaction(function () use ($request, $validated, $shopId) {
            $userId = $request->user()->id;
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $carts = PosCart::where('user_id', $request->user()->id)
                ->where('point_of_sale_id',$shopId)
                ->orderBy('product_id')
                ->get();

            if ($carts->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => __('The cart is empty.'),
                ]);
            }

            $order = Order::create([
                'point_of_sale_id'=>$shopId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $request->user()->id,
            ]);

            $productTotal = 0.0;
            $subTotal = 0.0;
            $maxStoredAmount = 99999999.99;

            foreach ($carts as $cart) {
                $product = Product::whereKey($cart->product_id)->lockForUpdate()->firstOrFail();

                if (!$product->status || BigDecimal::of(app(StockService::class)->available($shopId,(int)$product->id))->isLessThan((string)$cart->quantity)) {
                    throw ValidationException::withMessages([
                        'cart' => __('A product is no longer available in the requested quantity.'),
                    ]);
                }

                $unitPrice = (float) $product->price;
                $discountedUnitPrice = (float) $product->discounted_price;
                if ($unitPrice < 0 || $discountedUnitPrice < 0 || $discountedUnitPrice > $unitPrice) {
                    throw ValidationException::withMessages([
                        'cart' => __('A product has an invalid sale price.'),
                    ]);
                }

                $lineSubTotal = round($unitPrice * $cart->quantity, 2);
                $lineTotal = round($discountedUnitPrice * $cart->quantity, 2);
                $lineDiscount = round($lineSubTotal - $lineTotal, 2);

                if ($lineSubTotal > $maxStoredAmount || $lineTotal > $maxStoredAmount || $productTotal + $lineTotal > $maxStoredAmount || $subTotal + $lineSubTotal > $maxStoredAmount) {
                    throw ValidationException::withMessages([
                        'cart' => __('The sale total exceeds the supported limit.'),
                    ]);
                }

                $line = $order->products()->create([
                    'quantity' => $cart->quantity,
                    'price' => $unitPrice,
                    'purchase_price' => $product->purchase_price,
                    'sub_total' => $lineSubTotal,
                    'discount' => $lineDiscount,
                    'total' => $lineTotal,
                    'product_id' => $product->id,
                ]);

                app(StockService::class)->decrease($shopId,(int)$product->id,(string)$cart->quantity,[
                    'correlation_key'=>'sale:line:'.$line->id,
                    'order_product_id'=>$line->id,
                    'user_id'=>$userId,
                ]);
                $productTotal += $lineTotal;
                $subTotal += $lineSubTotal;
            }

            $discount = (float) ($validated['order_discount'] ?? 0);
            if ($discount > $productTotal) {
                throw ValidationException::withMessages([
                    'order_discount' => __('The order discount cannot exceed the sale total.'),
                ]);
            }

            $total = round($productTotal - $discount, 2);
            $tendered = (float) ($validated['paid'] ?? 0);
            $paid = round(min($tendered, $total), 2);
            $change = round(max($tendered - $total, 0), 2);
            $due = round($total - $paid, 2);

            $order->sub_total = round($productTotal, 2);
            $order->discount = $discount;
            $order->paid = $paid;
            $order->change_amount = $change;
            $order->total = $total;
            $order->due = $due;
            $order->status = $due <= 0;
            $order->save();

            if ($paid > 0) {
                $order->transactions()->create([
                    'amount' => $paid,
                    'customer_id' => $order->customer_id,
                    'user_id' => $request->user()->id,
                    'paid_by' => 'cash',
                ]);
            }

            PosCart::where('user_id', $userId)->where('point_of_sale_id',$shopId)->delete();

            return $order->fresh();
        });

        return response()->json([
            'message' => __('Order completed successfully'),
            'order' => $order,
        ], 200);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function invoice($id)
    {
        $order = \App\Support\StockDocumentAccess::query(Order::query(),request())->with(['customer', 'products.product'])->findOrFail($id);
        return view('backend.orders.print-invoice', compact('order'));
    }
    public function collection(Request $request, $id)
    {

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'amount' => ['required', 'numeric', 'min:1'],
            ]);

            $transaction = DB::transaction(function () use ($id, $data) {
                $order = \App\Support\StockDocumentAccess::query(Order::query(),request())->whereKey($id)->lockForUpdate()->firstOrFail();
                $amount = round((float) $data['amount'], 2);

                if ($amount > (float) $order->due) {
                    throw ValidationException::withMessages([
                        'amount' => __('The amount cannot exceed the remaining balance.'),
                    ]);
                }

                $order->due = round((float) $order->due - $amount, 2);
                $order->paid = round((float) $order->paid + $amount, 2);
                $order->status = $order->due <= 0;
                $order->save();

                return $order->transactions()->create([
                    'amount' => $amount,
                    'customer_id' => $order->customer_id,
                    'user_id' => auth()->id(),
                    'paid_by' => 'cash',
                ]);
            });

            return to_route('backend.admin.collectionInvoice', $transaction->id);
        }

        $order = \App\Support\StockDocumentAccess::query(Order::query(),$request)->findOrFail($id);
        return view('backend.orders.collection.create', compact('order'));
    }
    //collection invoice by order_transaction id
    public function collectionInvoice($id)
    {
        $transaction = OrderTransaction::findOrFail($id);
        $collection_amount = $transaction->amount;
        $order = $transaction->order;
        \App\Support\StockDocumentAccess::query(Order::query(),request())->whereKey($order->id)->firstOrFail();
        return view('backend.orders.collection.invoice', compact('order', 'collection_amount', 'transaction'));
    }
    //transactions by order id
    public function transactions($id)
    {
        $order = \App\Support\StockDocumentAccess::query(Order::query(),request())->with('transactions')->findOrFail($id);
        return view('backend.orders.collection.index', compact('order'));
    }

    public function posInvoice($id)
    {
        $order = \App\Support\StockDocumentAccess::query(Order::query(),request())->with(['customer', 'products.product'])->findOrFail($id);
        $maxWidth = readConfig('receiptMaxwidth')??'300px';
        return view('backend.orders.pos-invoice', compact('order', 'maxWidth'));
    }
}

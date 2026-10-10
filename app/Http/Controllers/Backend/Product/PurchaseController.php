<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Payment;
use App\Support\StockContext;
use App\Services\ReceiptStockService;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $purchases = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->with('supplier')->withCount(['receipts','paymentAllocations'])->latest();
            return DataTables::of($purchases)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs actions
                // cote page. L'URL de modification porte un parametre de requete,
                // elle est donc construite ici (un gabarit serait encode).
                ->addColumn('purchase_id', fn($data) => $data->id)
                ->addColumn('edit_url', fn($data) => route('backend.admin.purchase.products',$data->id))
                ->addColumn('can_amend',fn($data)=>$data->receipt_status==='pending' && $data->receipts_count===0 && $data->payment_allocations_count===0)
                ->addColumn('supplier', fn ($data) => $data->supplier?->name)
                ->editColumn('id', fn ($data) => '#' . $data->id)
                ->editColumn('total', fn ($data) => (string) \Brick\Math\BigDecimal::of((string) $data->grand_total)->toScale(2, \Brick\Math\RoundingMode::HalfUp).' '.($data->currency_code ?? __('Historical currency unknown')))
                ->editColumn('created_at', fn ($data) => Carbon::parse($data->date)->translatedFormat('d M, Y'))
                ->addColumn('action', function ($data) {
                    $actions = '<div class="btn-group"><button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Actions')) . '</button>';
                    $actions .= '<button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false"><span class="sr-only">' . e(__('Toggle Dropdown')) . '</span></button><div class="dropdown-menu" role="menu">';
                    $actions .= '<a class="dropdown-item" href="' . e(route('backend.admin.purchase.products', $data->id)) . '"><i class="fas fa-eye"></i> ' . e(__('View / Receive / Pay')) . '</a></div></div>';
                    return $actions;
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('backend.purchase.index');
    }

    public function create(Request $request)
    {
        abort_if(!auth()->user()->can($request->filled('purchase_id') ? 'purchase_update' : 'purchase_create'), 403);
        if ($request->filled('purchase_id')) return to_route('backend.admin.purchase.products', $request->integer('purchase_id'));
        return view('backend.purchase.create');
    }

    public function store(Request $request)
    {
        $purchaseId = $request->input('purchase_id');
        abort_if(!auth()->user()->can($purchaseId ? 'purchase_update' : 'purchase_create'), 403);
        $shopId = StockContext::shop($request)->id;
        if ($purchaseId) {
            throw ValidationException::withMessages(['purchase_id'=>__('A received or historical purchase cannot be rewritten; record a traced correction.')]);
        }

        $validated = $request->validate([
            'idempotency_key'=>['required','string','min:8','max:96'],
            'purchase_id' => ['prohibited'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'supplierId' => ['required', 'integer', 'exists:suppliers,id'],
            'products' => ['required', 'array', 'min:1', 'max:500'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.product_unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'products.*.qty' => ['required', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D'],
            'products.*.received_qty' => ['nullable', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D'],
            'products.*.purchase_price' => ['required', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'products.*.price' => ['required', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'products.*.expiry_status' => ['nullable', 'in:dated,not_applicable,unknown'],
            'products.*.expires_on' => ['nullable', 'date_format:Y-m-d'],
            'totals' => ['nullable', 'array'],
            'totals.tax' => ['nullable', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'totals.discount' => ['nullable', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'totals.shipping' => ['nullable', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
        ]);
        foreach ($validated['products'] as $index => $line) {
            if (($line['expiry_status'] ?? null) === 'dated' && empty($line['expires_on'])) {
                throw ValidationException::withMessages(["products.$index.expires_on" => __('A dated batch requires an expiry date.')]);
            }
            if (($line['expiry_status'] ?? null) !== 'dated' && !empty($line['expires_on'])) {
                throw ValidationException::withMessages(["products.$index.expires_on" => __('Only dated batches may have an expiry date.')]);
            }
        }
        $items = array_map(fn ($line) => [
            'product_id' => $line['id'], 'product_unit_id' => $line['product_unit_id'],
            'quantity' => $line['qty'], 'received_quantity' => $line['received_qty'] ?? $line['qty'],
            'unit_cost' => $line['purchase_price'], 'sale_price' => $line['price'],
            'expiry_status' => $line['expiry_status'] ?? 'unknown', 'expires_on' => $line['expires_on'] ?? null,
        ], $validated['products']);
        if (collect($items)->contains(fn($line)=>\Brick\Math\BigDecimal::of($line['received_quantity'])->isPositive())) {
            abort_unless($request->user()->can('purchase_receive'),403);
        }
        $purchase = app(PurchaseService::class)->create([
            'idempotency_key'=>$validated['idempotency_key'],
            'date' => Carbon::parse($validated['date'], 'Africa/Douala')->toDateString(),
            'due_date' => $validated['due_date'] ?? null, 'supplier_id' => $validated['supplierId'],
            'items' => $items, 'tax' => $validated['totals']['tax'] ?? '0',
            'discount' => $validated['totals']['discount'] ?? '0', 'shipping' => $validated['totals']['shipping'] ?? '0',
        ], StockContext::shop($request), (int)$request->user()->id);
        $purchase = app(\App\Services\StockAvailability::class)->purchase($purchase);
        $blocked = collect($items)->contains(fn($line)=>$line['expiry_status']==='unknown' && \Brick\Math\BigDecimal::of($line['received_quantity'])->isPositive());
        return response()->json(['message' => $blocked ? __('Purchase received; expiry information is missing, so this stock is blocked.') : __('Purchase saved successfully.'), 'purchase' => $purchase], 201);
    }

    public function amend(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('purchase_update'),403);
        $data = $request->validate([
            'idempotency_key'=>['required','string','min:8','max:96'],'reason'=>['required','string','max:255'],
            'due_date'=>['nullable','date','after_or_equal:'.$purchase->date],
            'items'=>['required','array','min:1','max:500'],'items.*.purchase_item_id'=>['required','integer','distinct'],
            'items.*.quantity'=>['required','string','regex:/\A(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D'],
            'items.*.unit_cost'=>['required','string','regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
        ]);
        $purchase = app(PurchaseService::class)->amend($purchase,$data,StockContext::shop($request),(int)$request->user()->id);
        if (!$request->expectsJson()) return to_route('backend.admin.purchase.products',$purchase->id)->with('success',__('Purchase amendment recorded.'));
        return response()->json(['purchase'=>$purchase]);
    }

    public function receive(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('purchase_receive'), 403);
        $shop = StockContext::shop($request);
        abort_unless((int)$purchase->point_of_sale_id === (int)$shop->id, 404);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'min:8', 'max:96'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.purchase_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,13})(?:\.[0-9]{1,6})?\z/D'],
            'items.*.unit_cost' => ['nullable', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'items.*.expiry_status' => ['nullable', 'in:dated,not_applicable,unknown'],
            'items.*.expires_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
        foreach ($data['items'] as $i => $item) {
            if (($item['expiry_status'] ?? null) === 'dated' && empty($item['expires_on'])) throw ValidationException::withMessages(["items.$i.expires_on" => __('A dated batch requires an expiry date.')]);
            if (($item['expiry_status'] ?? null) !== 'dated' && !empty($item['expires_on'])) throw ValidationException::withMessages(["items.$i.expires_on" => __('Only dated batches may have an expiry date.')]);
        }
        $receipt = app(PurchaseService::class)->receive($purchase, $data['items'], $shop, (int)$request->user()->id, $data['idempotency_key']);
        if (!$request->expectsJson()) return to_route('backend.admin.purchase.products', $purchase->id)->with('success', __('Purchase receipt recorded.'));
        return response()->json(['receipt' => $receipt, 'purchase' => $purchase->fresh()]);
    }

    public function pay(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('purchase_pay'), 403);
        $shop = StockContext::shop($request);
        abort_unless((int)$purchase->point_of_sale_id === (int)$shop->id, 404);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'min:8', 'max:96'],
            'amount' => ['required', 'string', 'regex:/\A\d{1,14}(?:\.\d{1,6})?\z/D'],
            'method' => ['required', 'string'],
            'currency_code' => ['nullable', 'regex:/\A[A-Z]{3}\z/D'],
            'external_reference' => ['nullable', 'string', 'max:128'],
        ]);
        if ($data['method'] === 'card' && empty($data['external_reference'])) {
            throw ValidationException::withMessages(['external_reference' => __('Enter the external terminal reference for a card payment.')]);
        }
        $payment = app(PurchaseService::class)->paySupplier($purchase, $data, $shop, (int)$request->user()->id);
        if (!$request->expectsJson()) return to_route('backend.admin.purchase.products', $purchase->id)->with('success', __('Supplier payment recorded.'));
        return response()->json(['payment' => $payment, 'purchase' => $purchase->fresh()]);
    }

    public function show(Request $request, $id)
    {
        if ($request->wantsJson()) {
            $purchase = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->findOrFail($id);
            return app(\App\Services\StockAvailability::class)->purchase($purchase);
        }
        abort(404);
    }

    public function reversePayment(Request $request, Purchase $purchase, Payment $payment)
    {
        abort_unless($request->user()->can('purchase_pay'), 403);
        $data = $request->validate(['reason'=>['required','string','max:255'],'method'=>['required','string'],'external_reference'=>['nullable','string','max:128']]);
        if ($data['method'] === 'card' && empty($data['external_reference'])) throw ValidationException::withMessages(['external_reference'=>__('Enter the external terminal reference for a card payment.')]);
        $reversal = app(PurchaseService::class)->reversePayment($purchase, $payment, $data, StockContext::shop($request), (int)$request->user()->id);
        if (!$request->expectsJson()) return to_route('backend.admin.purchase.products', $purchase->id)->with('success', __('Supplier payment reversal recorded.'));
        return response()->json(['payment'=>$reversal]);
    }

    public function cancel(Request $request, Purchase $purchase)
    {
        abort_unless($request->user()->can('purchase_cancel'), 403);
        $data = $request->validate(['reason'=>['required','string','max:255']]);
        $purchase = app(PurchaseService::class)->cancel($purchase, $data['reason'], StockContext::shop($request), (int)$request->user()->id);
        if (!$request->expectsJson()) return to_route('backend.admin.purchase.products', $purchase->id)->with('success', __('Purchase cancelled with traced stock reversals.'));
        return response()->json(['purchase'=>$purchase]);
    }

    public function edit($id)
    {
        return to_route('backend.admin.purchase.create', ['purchase_id' => $id]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        abort(405);
    }

    public function destroy(Purchase $purchase)
    {
        abort(405, __('Deleting purchases is not supported yet.'));
    }

    public function purchaseProducts(Request $request, $id)
    {
        $purchase = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->with(['items.product.unit','items.productUnit','items.receipts','receipts.items','paymentAllocations.payment.reversal'])->findOrFail($id);
        app(\App\Services\StockAvailability::class)->purchase($purchase);
        foreach ($purchase->items as $item) {
            $received = \Brick\Math\BigDecimal::zero();
            foreach ($item->receipts as $receipt) $received = $received->plus($receipt->entered_quantity);
            $item->setAttribute('received_quantity', (string)$received);
            $item->setAttribute('outstanding_quantity', (string)\Brick\Math\BigDecimal::of((string)($item->entered_quantity ?? $item->quantity))->minus($received));
        }
        $paid = \Brick\Math\BigDecimal::zero();
        foreach ($purchase->paymentAllocations as $allocation) $paid = $allocation->payment->direction === 'outgoing' ? $paid->plus($allocation->amount) : $paid->minus($allocation->amount);
        $purchase->setAttribute('paid_amount', $purchase->payment_status === 'unknown' ? null : (string)$paid);
        $purchase->setAttribute('due_amount', $purchase->payment_status === 'unknown' ? null : (($purchase->cancelled_at || $purchase->payment_status === 'not_applicable') ? '0.000000' : (string)\Brick\Math\BigDecimal::of((string)$purchase->grand_total)->minus($paid)));
        return view('backend.purchase.products', compact('id', 'purchase'));
    }
}

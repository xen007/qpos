<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\StockContext;
use App\Services\ReceiptStockService;
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
            $purchases = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->with('supplier')->latest();
            return DataTables::of($purchases)
                ->addIndexColumn()
                // Colonnes neutres : les pages migrees composent leurs actions
                // cote page. L'URL de modification porte un parametre de requete,
                // elle est donc construite ici (un gabarit serait encode).
                ->addColumn('purchase_id', fn($data) => $data->id)
                ->addColumn('edit_url', fn($data) => route('backend.admin.purchase.create', ['purchase_id' => $data->id]))
                ->addColumn('supplier', fn ($data) => $data->supplier?->name)
                ->editColumn('id', fn ($data) => '#' . $data->id)
                ->editColumn('total', fn ($data) => $data->grand_total)
                ->editColumn('created_at', fn ($data) => Carbon::parse($data->date)->translatedFormat('d M, Y'))
                ->addColumn('action', function ($data) {
                    $actions = '<div class="btn-group"><button type="button" class="btn bg-gradient-primary btn-flat">' . e(__('Actions')) . '</button>';
                    $actions .= '<button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false"><span class="sr-only">' . e(__('Toggle Dropdown')) . '</span></button><div class="dropdown-menu" role="menu">';
                    if (auth()->user()->can('purchase_update')) {
                        $actions .= '<a class="dropdown-item" href="' . e(route('backend.admin.purchase.create', ['purchase_id' => $data->id])) . '"><i class="fas fa-edit"></i> ' . e(__('Edit')) . '</a>';
                    }
                    $actions .= '<a class="dropdown-item" href="' . e(route('backend.admin.purchase.products', $data->id)) . '"><i class="fas fa-eye"></i> ' . e(__('View')) . '</a></div></div>';
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
            'purchase_id' => ['nullable', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'supplierId' => ['required', 'integer', 'exists:suppliers,id'],
            'products' => ['required', 'array', 'min:1', 'max:500'],
            'products.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'products.*.item_id' => ['nullable', 'integer', 'min:1'],
            'products.*.purchase_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'products.*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'products.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'products.*.expiry_status' => ['nullable', 'in:dated,not_applicable,unknown'],
            'products.*.expires_on' => ['nullable','date_format:Y-m-d'],
            'totals' => ['nullable', 'array'],
            'totals.tax' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'totals.discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'totals.shipping' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $tax = round((float) ($validated['totals']['tax'] ?? 0), 2);
        $discount = round((float) ($validated['totals']['discount'] ?? 0), 2);
        $shipping = round((float) ($validated['totals']['shipping'] ?? 0), 2);
        $subTotal = round(collect($validated['products'])->sum(fn ($item) => round((float) $item['purchase_price'] * (int) $item['qty'], 2)), 2);
        if ($subTotal + $tax + $shipping > 99999999.99) {
            throw ValidationException::withMessages(['products' => __('The purchase total exceeds the supported limit.')]);
        }
        if ($discount > $subTotal + $tax + $shipping) {
            throw ValidationException::withMessages(['totals.discount' => __('The discount cannot exceed the purchase total.')]);
        }
        $grandTotal = round($subTotal + $tax - $discount + $shipping, 2);

        $purchase = DB::transaction(function () use ($validated, $purchaseId, $tax, $discount, $shipping, $subTotal, $grandTotal, $shopId) {
            $purchase = new Purchase();
            if (collect($validated['products'])->pluck('item_id')->filter()->isNotEmpty()) {
                throw ValidationException::withMessages(['products' => __('One or more purchase items are invalid.')]);
            }

            $purchase->fill([
                'point_of_sale_id'=>$shopId,
                'supplier_id' => $validated['supplierId'],
                'user_id' => auth()->id(),
                'sub_total' => $subTotal,
                'tax' => $tax,
                'discount_value' => $discount,
                'discount_type' => 'fixed',
                'shipping' => $shipping,
                'grand_total' => $grandTotal,
                'date' => Carbon::parse($validated['date'])->toDateString(),
                'status' => 1,
            ])->save();

            $productIds = collect($validated['products'])->pluck('id')->unique()->sort()->values();
            $lockedProducts = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($lockedProducts->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['products' => __('One or more products are unavailable.')]);
            }

            foreach ($validated['products'] as $item) {
                $receiptItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['id'],
                    'purchase_price' => round((float) $item['purchase_price'], 2),
                    'price' => round((float) $item['price'], 2),
                    'quantity' => (int) $item['qty'],
                ]);
                app(ReceiptStockService::class)->receive($receiptItem,$shopId,(int)auth()->id(),[
                    'expiry_status'=>$item['expiry_status'] ?? 'unknown','expires_on'=>$item['expires_on'] ?? null,
                ]);
            }

            return app(\App\Services\StockAvailability::class)->purchase($purchase);
        });

        return response()->json([
            'message' => collect($validated['products'])->contains(fn ($item) => ($item['expiry_status'] ?? 'unknown') === 'unknown')
                ? __('Purchase received; expiry information is missing, so this stock is blocked.')
                : __('Purchase saved successfully.'),
            'purchase' => $purchase,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        if ($request->wantsJson()) {
            $purchase = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->findOrFail($id);
            return app(\App\Services\StockAvailability::class)->purchase($purchase);
        }
        abort(404);
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
        $purchase = \App\Support\StockDocumentAccess::query(Purchase::query(),$request)->with('items.product')->findOrFail($id);
        app(\App\Services\StockAvailability::class)->purchase($purchase);
        return view('backend.purchase.products', compact('id', 'purchase'));
    }
}

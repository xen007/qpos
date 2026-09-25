<?php

namespace App\Http\Controllers\Backend\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('purchase_view'), 403);

        if ($request->ajax()) {
            $purchases = Purchase::query()->with('supplier')->latest();
            return DataTables::of($purchases)
                ->addIndexColumn()
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

        $purchase = DB::transaction(function () use ($validated, $purchaseId, $tax, $discount, $shipping, $subTotal, $grandTotal) {
            $purchase = $purchaseId
                ? Purchase::whereKey($purchaseId)->lockForUpdate()->firstOrFail()
                : new Purchase();

            $items = $purchaseId ? $purchase->items()->lockForUpdate()->get() : collect();
            $submittedItemIds = collect($validated['products'])->pluck('item_id')->filter()->map(fn ($id) => (int) $id);
            $ownedItemIds = $items->pluck('id')->map(fn ($id) => (int) $id);
            if ($submittedItemIds->diff($ownedItemIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['products' => __('One or more purchase items are invalid.')]);
            }

            $purchase->fill([
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

            $productIds = collect($validated['products'])->pluck('id')->merge($items->pluck('product_id'))->unique()->sort()->values();
            $lockedProducts = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($lockedProducts->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['products' => __('One or more products are unavailable.')]);
            }

            $oldQuantities = $items->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
            $newQuantities = collect($validated['products'])->groupBy('id')->map(fn ($rows) => (int) $rows->sum('qty'));
            foreach ($productIds as $productId) {
                $stockDelta = (int) ($newQuantities[$productId] ?? 0) - (int) ($oldQuantities[$productId] ?? 0);
                if ($stockDelta < 0 && (int) $lockedProducts[$productId]->quantity < abs($stockDelta)) {
                    throw ValidationException::withMessages(['products' => __('This purchase cannot be reduced because some of its stock has already been used.')]);
                }
                if ($stockDelta !== 0) {
                    $lockedProducts[$productId]->increment('quantity', $stockDelta);
                }
            }
            $purchase->items()->delete();

            foreach ($validated['products'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['id'],
                    'purchase_price' => round((float) $item['purchase_price'], 2),
                    'price' => round((float) $item['price'], 2),
                    'quantity' => (int) $item['qty'],
                ]);
            }

            return $purchase->load('items', 'supplier');
        });

        return response()->json([
            'message' => __('Purchase saved successfully.'),
            'purchase' => $purchase,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        abort_if(!auth()->user()->can('purchase_view'), 403);
        if ($request->wantsJson()) {
            return Purchase::with('items.product', 'supplier')->findOrFail($id);
        }
        abort(404);
    }

    public function edit($id)
    {
        abort_if(!auth()->user()->can('purchase_update'), 403);
        return to_route('backend.admin.purchase.create', ['purchase_id' => $id]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        abort_if(!auth()->user()->can('purchase_update'), 403);
        abort(405);
    }

    public function destroy(Purchase $purchase)
    {
        abort_if(!auth()->user()->can('purchase_delete'), 403);
        abort(405, __('Deleting purchases is not supported yet.'));
    }

    public function purchaseProducts(Request $request, $id)
    {
        abort_if(!auth()->user()->can('purchase_view'), 403);
        $purchase = Purchase::with('items.product')->findOrFail($id);
        return view('backend.purchase.products', compact('id', 'purchase'));
    }
}

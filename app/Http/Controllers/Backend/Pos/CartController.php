<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosCart;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\SaleService;
use App\Services\StockAvailability;
use App\Support\QuantityDecimal;
use App\Support\SaleOperation as Op;
use App\Support\StockContext;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    private function cart(Request $r)
    {
        $r->validate(['cart_id' => ['required', 'regex:/\A[a-zA-Z0-9_-]{16,64}\z/D']]);

        return PosCart::where('user_id', $r->user()->id)->where('point_of_sale_id', StockContext::shop($r)->id)->where('cart_id', $r->cart_id);
    }

    public function index(Request $r)
    {
        if (! $r->wantsJson()) {
            return view('backend.cart.index');
        }
        $this->cart($r);
        $r->validate(['customer_id' => 'required|integer|exists:customers,id', 'order_discount' => 'nullable|string']);

        return response()->json(app(SaleService::class)->quote($r->user()->id, StockContext::shop($r)->id, $r->all()));
    }

    public function getProducts(Request $r)
    {
        $shop = StockContext::shop($r)->id;
        $r->validate(['search' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255']);
        $q = ProductUnit::with(['product' => fn ($p) => app(StockAvailability::class)->attach($p->getQuery(), $shop), 'unit'])->where('is_active', true)->whereNotNull('sale_price_ttc')->whereHas('product', fn ($p) => $p->where('status', true)->whereNotNull('allows_fractional'));
        if ($r->filled('search')) {
            $q->where(fn ($q) => $q->where('label', 'like', '%'.$r->search.'%')->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$r->search.'%')->orWhere('sku', 'like', '%'.$r->search.'%')));
        }
        if ($r->filled('barcode')) {
            $code = ProductBarcode::where('barcode', trim($r->barcode))->where('is_active', true)->first();
            if ($code) {
                $q->whereKey($code->product_unit_id);
            } else {
                $q->where('is_reference', true)->whereHas('product', fn ($p) => $p->where('sku', trim($r->barcode)));
            }
        }
        $page = $q->orderBy('product_id')->orderBy('id')->paginate(48);
        $data = $page->getCollection()->map(function ($u) {
            return ['id' => $u->id, 'product_unit_id' => $u->id, 'name' => $u->product->name.' / '.$u->label, 'image' => $u->product->image, 'quantity' => $u->product->stock_available ?? '0.000000', 'expired_quantity' => $u->product->stock_expired ?? '0.000000', 'factor' => $u->factor, 'price' => $u->sale_price_ttc];
        });

        return response()->json(['data' => $data, 'meta' => ['last_page' => $page->lastPage()]]);
    }

    public function store(Request $r)
    {
        $r->validate(['product_unit_id' => 'required|integer|exists:product_units,id', 'quantity' => 'nullable|string']);
        $this->cart($r);
        DB::transaction(function () use ($r) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $u = ProductUnit::with('product')->whereKey($r->product_unit_id)->firstOrFail();
            if (! $u->is_active || ! $u->product->status || $u->product->allows_fractional === null || $u->sale_price_ttc === null) {
                Op::fail('product_unit_id', 'Configure the active product and packaging before calculating a price.');
            }
            $v = QuantityDecimal::parse($r->quantity ?? '1', 'quantity', true);
            $row = $this->cart($r)->where('product_unit_id', $u->id)->first();
            $v = $v->plus($row?->quantity ?? '0');
            QuantityDecimal::toBase((string) $v, $u->factor, $u->product->allows_fractional);
            if ($row) {
                $row->update(['quantity' => (string) $v]);
            } else {
                PosCart::create(['user_id' => $r->user()->id, 'point_of_sale_id' => StockContext::shop($r)->id, 'cart_id' => $r->cart_id, 'product_id' => $u->product_id, 'product_unit_id' => $u->id, 'quantity' => (string) $v]);
            }
        }, 3);

        return response()->json(['message' => __('Cart updated')]);
    }

    public function increment(Request $r)
    {
        return $this->change($r, '1');
    }

    public function decrement(Request $r)
    {
        return $this->change($r, '-1');
    }

    public function quantity(Request $r)
    {
        $r->validate(['quantity' => 'required|string']);

        return $this->change($r, null);
    }

    private function change(Request $r, ?string $delta)
    {
        $r->validate(['id' => 'required|integer']);
        $this->cart($r);
        DB::transaction(function () use ($r, $delta) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $row = $this->cart($r)->whereKey($r->id)->with('productUnit.product')->lockForUpdate()->firstOrFail();
            $v = $delta === null ? QuantityDecimal::parse($r->quantity, 'quantity', true) : BigDecimal::of($row->quantity)->plus($delta);
            if (! $v->isPositive()) {
                $row->delete();

                return;
            }QuantityDecimal::toBase((string) $v, $row->productUnit->factor, $row->productUnit->product->allows_fractional);
            $row->update(['quantity' => (string) $v]);
        }, 3);

        return response()->json(['message' => __('Cart updated')]);
    }

    public function delete(Request $r)
    {
        $r->validate(['id' => 'required|integer']);

        return $this->remove($r, false);
    }

    public function empty(Request $r)
    {
        return $this->remove($r, true);
    }

    private function remove(Request $r, bool $all)
    {
        $this->cart($r);
        DB::transaction(function () use ($r, $all) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $q = $this->cart($r);
            if (! $all) {
                $q->whereKey($r->id);
            }$q->delete();
        }, 3);

        return response()->json(['message' => __('Cart updated')]);
    }
}

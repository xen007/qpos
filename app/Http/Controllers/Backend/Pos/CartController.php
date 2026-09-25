<?php

namespace App\Http\Controllers\Backend\Pos;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\PosCart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePos();
        if ($request->wantsJson()) {
            $cartItems = PosCart::where('user_id', auth()->id())
                ->with('product')
                ->latest('created_at')
                ->get()
                ->map(function ($item) {
                    // Calculate row total for each item
                    $item->row_total = round(($item->quantity * $item->product->discounted_price),2);
                    return $item;
                });
            $total = $cartItems->sum('row_total');
            return response()->json([
                'carts' => $cartItems,
                'total' => round($total, 2)
            ]);
        }
        // clear cart

        return view('backend.cart.index');
    }
    public function getProducts(Request $request)
    {
        $this->authorizePos();

        $products = Product::query()->active()->stocked();
        // Search by name if provided
        $products->when($request->search, function ($query, $search) {
            $query->where('name', 'LIKE', "%{$search}%");
        });

        // Search by barcode if provided
        $products->when($request->barcode, function ($query, $barcode) {
            $query->where('sku', $barcode);
        });
        $products = $products->latest()->paginate(96);
        if (request()->wantsJson()) {
            return ProductResource::collection($products);
        }
    }

    public function store(Request $request)
    {
        $this->authorizePos();
        // Validate request input
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product_id = $request->id;

        return DB::transaction(function () use ($product_id) {
            $userId = auth()->id();
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($product_id)->lockForUpdate()->firstOrFail();
            $cartItem = PosCart::where('user_id', $userId)
                ->where('product_id', $product_id)
                ->lockForUpdate()
                ->first();

            if (!$product->status) {
                return response()->json(['message' => __('Product is not available')], 400);
            }
            if ($product->quantity <= 0) {
                return response()->json(['message' => __('Insufficient stock available')], 400);
            }

            if ($cartItem) {
                if ($cartItem->quantity >= $product->quantity) {
                    return response()->json(['message' => __('Cannot add more, stock limit reached')], 400);
                }
                $cartItem->increment('quantity');
                return response()->json(['message' => __('Quantity updated'), 'quantity' => $cartItem->quantity], 200);
            }

            PosCart::create([
                'user_id' => $userId,
                'product_id' => $product_id,
                'quantity' => 1,
            ]);
            return response()->json(['message' => __('Product added to cart'), 'quantity' => 1], 201);
        });
    }

    public function increment(Request $request)
    {
        $this->authorizePos();
        $this->validateCartRequest($request);

        return DB::transaction(function () use ($request) {
            $userId = auth()->id();
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $cartSnapshot = PosCart::where('user_id', $userId)->whereKey($request->id)->firstOrFail();
            $product = Product::whereKey($cartSnapshot->product_id)->lockForUpdate()->firstOrFail();
            $cart = PosCart::where('user_id', $userId)->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($product->quantity <= 0) {
                return response()->json(['message' => __('Insufficient stock available')], 400);
            }
            if ($cart->quantity >= $product->quantity) {
                return response()->json(['message' => __('Cannot add more, stock limit reached')], 400);
            }
            $cart->increment('quantity');
            return response()->json(['message' => __('Cart Updated successfully')], 200);
        });
    }
    public function decrement(Request $request)
    {
        $this->authorizePos();
        $this->validateCartRequest($request);
        return DB::transaction(function () use ($request) {
            $userId = auth()->id();
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $cart = PosCart::where('user_id', $userId)->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($cart->quantity <= 1) {
                return response()->json(['message' => __('Quantity cannot be less than 1.')], 400);
            }
            $cart->decrement('quantity');
            return response()->json(['message' => __('Cart Updated successfully')], 200);
        });
    }
    public function delete(Request $request)
    {
        $this->authorizePos();
        $this->validateCartRequest($request);

        return DB::transaction(function () use ($request) {
            $userId = auth()->id();
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $cart = PosCart::where('user_id', $userId)->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $cart->delete();
            return response()->json(['message' => __('Item successfully deleted')], 200);
        });
    }
    public function empty()
    {
        $this->authorizePos();
        $deletedCount = DB::transaction(function () {
            $userId = auth()->id();
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            return PosCart::where('user_id', $userId)->delete();
        });

        if ($deletedCount > 0) {
            return response()->json(['message' => __('Cart successfully cleared')], 200);
        }

        return response()->json(['message' => __('Cart is already empty')], 204);
    }

    private function authorizePos(): void
    {
        abort_if(!auth()->user()->can('sale_create'), 403);
    }

    private function validateCartRequest(Request $request): void
    {
        $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('pos_carts', 'id')->where('user_id', auth()->id()),
            ],
        ]);
    }
}

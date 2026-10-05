<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** SQL projection of the same stock buckets used by StockService. */
final class StockAvailability
{
    public function purchase(Purchase $purchase): Purchase
    {
        $purchase->loadMissing('items','supplier');
        $products = Product::query()->whereIn('id',$purchase->items->pluck('product_id')->unique());
        $shopId = $purchase->point_of_sale_id;
        $projectionShop = $shopId ?? request()->attributes->get('point_of_sale')?->id;
        if ($projectionShop) { $this->attach($products,(int)$projectionShop); }
        else { $products->select('products.*')->selectRaw('NULL AS stock_available'); }
        $products = $products->get()->keyBy('id');
        foreach ($purchase->items as $item) {
            $product = $products->get($item->product_id);
            $item->setRelation('product',$product);
            $item->withStockValue($shopId ? (string)($product?->quantity ?? '0.000000') : null);
        }
        return $purchase;
    }

    public function attach(Builder $products, int $shopId): Builder
    {
        $blocked = DB::table('batch_stock')->join('product_batches', 'product_batches.id', '=', 'batch_stock.product_batch_id')
            ->whereColumn('product_batches.product_id', 'products.id')->where('batch_stock.point_of_sale_id', $shopId)
            ->where(fn ($q) => $q->where('product_batches.expiry_status', 'unknown')
                ->orWhere(fn ($q) => $q->where('product_batches.expiry_status', 'dated')
                    ->where('product_batches.expires_on', '<', now('Africa/Douala')->toDateString())))
            ->selectRaw('COALESCE(SUM(batch_stock.saleable_quantity), 0)');
        $available = DB::table('product_stock')->whereColumn('product_stock.product_id','products.id')
            ->where('product_stock.point_of_sale_id',$shopId)
            ->selectRaw('GREATEST(0, product_stock.saleable_quantity - ('.$blocked->toSql().'))', $blocked->getBindings());
        return $products->addSelect('products.*')->selectSub($available, 'stock_available');
    }
}

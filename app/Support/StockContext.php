<?php

namespace App\Support;

use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class StockContext
{
    public static function shop(Request $request): PointOfSale
    {
        $shop = $request->attributes->get('point_of_sale');
        abort_unless($shop instanceof PointOfSale && $request->user(), 403, __('Choose an active assigned shop.'));
        Gate::forUser($request->user())->authorize('view', $shop);
        return $shop;
    }
}

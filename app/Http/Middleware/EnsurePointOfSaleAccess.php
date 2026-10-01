<?php

namespace App\Http\Middleware;

use App\Models\PointOfSale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsurePointOfSaleAccess
{
    public function handle(Request $request, Closure $next, string $parameter = 'pointOfSale'): Response
    {
        $user = $request->user();
        $routeValue = $request->route($parameter);

        if (!$user || !$routeValue) {
            abort(403);
        }

        $pointOfSale = $routeValue instanceof PointOfSale
            ? $routeValue
            : PointOfSale::query()->findOrFail($routeValue);

        Gate::forUser($user)->authorize('view', $pointOfSale);
        $request->attributes->set('point_of_sale', $pointOfSale);

        return $next($request);
    }
}

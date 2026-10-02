<?php
namespace App\Http\Middleware;

use App\Models\PointOfSale;
use App\Support\PointOfSaleContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

class SetPointOfSaleContext
{
    public function handle(Request $request, Closure $next)
    {
        $contextField = $request->routeIs('backend.admin.shops.select') ? 'point_of_sale_id' : 'operation_point_of_sale_id';
        $choices = collect();
        $selected = null;
        if (PointOfSaleContext::ready()) {
            $user = $request->user();
            if ($user && !$user->is_suspended && $user->getRoleNames()->isNotEmpty() && $user->can('point_of_sale_access')) {
                $choices = PointOfSale::accessibleBy($user)->orderBy('name')->get();
                if ($request->has($contextField)) {
                    $data = $request->validate([$contextField => ['required', 'integer']]);
                    $selected = $choices->firstWhere('id', (int) $data[$contextField]);
                    abort_unless($selected, 403);
                    Gate::authorize('view', $selected);
                } else {
                    $preferred = $request->session()->get('active_point_of_sale_id', $user->preferred_point_of_sale_id);
                    $selected = $choices->firstWhere('id', (int) $preferred);
                }
            } elseif ($request->has($contextField)) {
                abort(403);
            }
            if (!$selected) {
                $request->session()->forget('active_point_of_sale_id');
            }
        }
        $request->attributes->set('point_of_sale', $selected);
        View::share('availablePointsOfSale', $choices);
        View::share('selectedPointOfSale', $selected);
        View::share('pointOfSaleContextReady', PointOfSaleContext::ready());
        return $next($request);
    }
}

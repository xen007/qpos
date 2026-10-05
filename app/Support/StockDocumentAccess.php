<?php

namespace App\Support;

use App\Models\PointOfSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class StockDocumentAccess
{
    public static function query(Builder $documents, Request $request): Builder
    {
        $user = $request->user();
        abort_unless($user,403);
        $column = $documents->getModel()->qualifyColumn('point_of_sale_id');
        // Legacy documents retain their existing permission access: no invented shop.
        return $documents->where(fn ($q) => $q->whereNull($column)
            ->orWhereIn($column, PointOfSale::accessibleBy($user)->select('points_of_sale.id')));
    }
}

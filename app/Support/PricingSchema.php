<?php
namespace App\Support;
use Illuminate\Support\Facades\Schema;
final class PricingSchema
{
    public static function ready(): bool
    {
        static $ready = null;
        return $ready ??= Schema::hasTable('promotions') && Schema::hasTable('price_rules')
            && Schema::hasColumn('product_units', 'reference_purchase_cost');
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class CatalogueSchema
{
    public static function ready(): bool
    {
        static $ready = null;
        return $ready ??= Schema::hasColumn('order_products', 'base_quantity')
            && Schema::hasColumn('units', 'is_active')
            && Schema::hasColumn('products', 'allows_fractional')
            && Schema::hasTable('product_units')
            && Schema::hasTable('product_barcodes');
    }
}
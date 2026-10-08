<?php

namespace App\Services;

use App\Models\OrderProduct;
use App\Models\OrderStockAllocation;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReportEvidence
{
    public function line(OrderProduct $line): void
    {
        if (! Schema::hasTable('report_line_snapshots')) return;
        $product = DB::table('products')->find($line->product_id);
        DB::table('report_line_snapshots')->insertOrIgnore([
            'order_product_id' => $line->id, 'category_id' => $product?->category_id,
            'category_label' => $product?->category_id ? DB::table('categories')->where('id', $product->category_id)->value('name') : null,
            'captured_at' => now('UTC'),
        ]);
    }

    public function allocation(OrderStockAllocation $a): void
    {
        if (! Schema::hasTable('report_allocation_snapshots')) return;
        $b = $a->product_batch_id ? ProductBatch::find($a->product_batch_id) : null;
        DB::table('report_allocation_snapshots')->insertOrIgnore([
            'order_stock_allocation_id' => $a->id,
            'cost_known' => $b && ! $b->cost_unknown && $a->unit_cost !== null && $a->total_cost !== null,
            'currency_code' => $b?->currency_code, 'captured_at' => now('UTC'),
        ]);
    }

    public function activity(string $type, int $id, int $shop): void
    {
        if (! Schema::hasTable('reporting_activity')) return;
        DB::table('reporting_activity')->insert([
            'point_of_sale_id' => $shop, 'source_type' => $type, 'source_id' => $id,
            'business_date' => now('Africa/Douala')->toDateString(), 'recorded_at' => now('UTC'),
        ]);
    }
}

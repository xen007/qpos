<?php
namespace App\Services;

use App\Support\ReportFilter;
use Illuminate\Support\Facades\DB;

/** Attribute only identified physical lot allocations, never catalogue associations. */
final class SupplierReportAllocation
{
    public function filterStock($query, int $supplier): void
    {
        $query->whereExists(fn($q)=>$q->selectRaw('1')->from('purchase_receipt_items as sri')
            ->join('purchase_receipts as sr','sr.id','=','sri.purchase_receipt_id')
            ->join('purchases as sp','sp.id','=','sr.purchase_id')
            ->whereColumn('sri.id','b.purchase_receipt_item_id')->where('sp.supplier_id',$supplier));
    }

    public function events($events, ReportFilter $filter)
    {
        $lines=DB::table('order_products as sl')->join('orders as so','so.id','=','sl.order_id')
            ->whereIn('so.point_of_sale_id',$filter->shops)->where('so.currency_code','XAF')->where('so.sale_state','<>','legacy')
            ->where('so.created_at','<',$filter->end->utc()->format('Y-m-d H:i:s'))->select('sl.id');
        $allocationWindows=DB::table('order_stock_allocations')->whereIn('order_product_id',$lines)
            ->select(['id','order_product_id','product_batch_id','base_quantity','total_cost'])
            ->selectRaw('SUM(base_quantity) OVER (PARTITION BY order_product_id ORDER BY id ROWS UNBOUNDED PRECEDING) AS cum')
            ->selectRaw('COALESCE(SUM(base_quantity) OVER (PARTITION BY order_product_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING),0) AS prev');
        $known="(cs.cost_known=1 AND cs.currency_code='XAF' AND a.total_cost IS NOT NULL)";
        $sale=DB::query()->fromSub($allocationWindows,'a');
        $this->provenance($sale,'a.product_batch_id',$filter->supplier);
        $sale->leftJoin('report_allocation_snapshots as cs','cs.order_stock_allocation_id','=','a.id')
            ->selectRaw("a.order_product_id AS line_key, 'sale' AS event_kind, a.base_quantity AS quantity, a.cum, a.prev")
            ->selectRaw("CASE WHEN $known THEN a.total_cost ELSE 0 END AS cogs, CASE WHEN $known THEN 0 ELSE a.base_quantity END AS unknown_quantity");

        // Windows precede supplier filtering, preserving allocation and return rounding.
        $returnWindows=DB::table('return_stock_allocations as rw')
            ->whereIn('rw.order_stock_allocation_id',DB::table('order_stock_allocations')->whereIn('order_product_id',$lines)->select('id'))
            ->select('rw.*')
            ->selectRaw('SUM(quantity) OVER (PARTITION BY return_item_id ORDER BY id ROWS UNBOUNDED PRECEDING) AS cum')
            ->selectRaw('COALESCE(SUM(quantity) OVER (PARTITION BY return_item_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING),0) AS prev')
            ->selectRaw('SUM(quantity) OVER (PARTITION BY order_stock_allocation_id ORDER BY id ROWS UNBOUNDED PRECEDING) AS cost_cum')
            ->selectRaw('COALESCE(SUM(quantity) OVER (PARTITION BY order_stock_allocation_id ORDER BY id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING),0) AS cost_prev');
        $returns=DB::query()->fromSub($returnWindows,'ra')
            ->join('order_stock_allocations as a','a.id','=','ra.order_stock_allocation_id')
            ->join('return_items as ri','ri.id','=','ra.return_item_id');
        $this->provenance($returns,'a.product_batch_id',$filter->supplier);
        $returns->leftJoin('report_allocation_snapshots as cs','cs.order_stock_allocation_id','=','a.id')
            ->selectRaw("ra.return_item_id AS line_key, 'return' AS event_kind, -ra.quantity AS quantity, ra.cum, ra.prev")
            ->selectRaw("-CASE WHEN ri.saleable=1 AND $known THEN ROUND(a.total_cost * ra.cost_cum / a.base_quantity,6)-ROUND(a.total_cost * ra.cost_prev / a.base_quantity,6) ELSE 0 END AS cogs")
            ->selectRaw("-CASE WHEN ri.saleable=1 AND NOT COALESCE($known,0) THEN ra.quantity ELSE 0 END AS unknown_quantity");
        $dimensions=['occurred_at','shop_id','seller_id','cashier_id','category_id','category_label','product_id','product_label','event_kind','source_id','line_key'];
        $query=DB::query()->fromSub($events,'e')->joinSub($sale->unionAll($returns),'sa',fn($j)=>$j->on('sa.line_key','=','e.line_key')->on('sa.event_kind','=','e.event_kind'));
        foreach($dimensions as $column)$query->addSelect('e.'.$column)->groupBy('e.'.$column);
        $query->selectRaw('SUM(ROUND(e.net_sales * sa.cum / NULLIF(ABS(e.quantity),0),6)-ROUND(e.net_sales * sa.prev / NULLIF(ABS(e.quantity),0),6)) AS net_sales')
            ->selectRaw('SUM(sa.cogs) AS cogs, SUM(sa.unknown_quantity) AS unknown_quantity, SUM(sa.quantity) AS quantity');
        return DB::query()->fromSub($query,'events');
    }

    private function provenance($query,string $batch,int $supplier): void
    {
        $query->join('product_batches as sb','sb.id','=',$batch)
            ->join('purchase_receipt_items as sri','sri.id','=','sb.purchase_receipt_item_id')
            ->join('purchase_receipts as sr','sr.id','=','sri.purchase_receipt_id')
            ->join('purchases as sp','sp.id','=','sr.purchase_id')->where('sp.supplier_id',$supplier);
    }
}

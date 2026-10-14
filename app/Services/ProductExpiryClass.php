<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
final class ProductExpiryClass
{
    public function forProducts(iterable $ids):array
    {
        $ids=collect($ids)->unique()->values()->all();
        if(!$ids)return [];
        return DB::table('product_batches')->whereIn('product_id',$ids)->whereIn('expiry_status',['dated','not_applicable'])
            ->select('product_id')->selectRaw("CASE WHEN COUNT(DISTINCT expiry_status)=1 THEN MIN(expiry_status) ELSE 'conflict' END AS classification")
            ->groupBy('product_id')->pluck('classification','product_id')->all();
    }
}

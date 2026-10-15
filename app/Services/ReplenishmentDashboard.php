<?php
namespace App\Services;
use App\Models\Product;
use App\Support\{ReportFilter,ReplenishmentFilters};
use Illuminate\Support\Facades\DB;

final class ReplenishmentDashboard
{
    public function data(ReportFilter $filter): array
    {
        $low=0;$out=0;$rows=collect();$categories=collect();
        foreach($filter->shops as $shop){
            $products=Product::query()->where('status',true)
                ->when($filter->product,fn($q)=>$q->where('products.id',$filter->product))
                ->when($filter->category,fn($q)=>$q->where('products.category_id',$filter->category));
            $stock=app(StockAvailability::class)->attach($products,$shop);
            $minimum=ReplenishmentFilters::valueSql('minimum',(int)$shop,(string)config('system.notification_low_stock_threshold',config('notifications.low_stock_threshold','10')));
            $q=DB::query()->fromSub($stock,'products')->whereRaw("COALESCE(stock_available,0)<=$minimum");
            $low+=(clone $q)->where('stock_available','>',0)->count();
            $out+=(clone $q)->whereRaw('COALESCE(stock_available,0)=0')->count();
            $rows=$rows->merge((clone $q)->select('products.id','products.name','products.sku','products.category_id','products.stock_available')
                ->selectRaw("$minimum AS minimum, ? AS shop_id",[$shop])->orderByRaw('COALESCE(stock_available,0)')->orderBy('products.id')->limit(20)->get());
            $categories=$categories->merge((clone $q)->leftJoin('categories as dc','dc.id','=','products.category_id')
                ->selectRaw("COALESCE(dc.name, ?) AS label, COUNT(*) AS total",[__('replenishment.uncategorized')])->groupBy('dc.name')->get());
        }
        $categories=$categories->groupBy('label')->map(fn($group,$label)=>['label'=>$label,'total'=>$group->sum('total')])->sortByDesc('total')->take(20)->values();
        return ['low'=>$low,'out'=>$out,'rows'=>$rows->sortBy(fn($r)=>(float)($r->stock_available??0))->take(20)->values(),'categories'=>$categories];
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class StockPolicyService
{
    public const FIELDS = ['minimum','optimal','maximum','alerts_enabled','maximum_enabled','hide_out_of_stock'];

    public function effective(int $shop, int $product, ?array $rows = null): array
    {
        $minimum=(string)config('system.notification_low_stock_threshold',config('notifications.low_stock_threshold','10'));
        $result=['minimum'=>$minimum,'optimal'=>(string)BigDecimal::max('50',$minimum),'maximum'=>null,'alerts_enabled'=>true,'maximum_enabled'=>false,'hide_out_of_stock'=>false];
        $origins=array_fill_keys(self::FIELDS,'default');
        $rows ??= Schema::hasTable('stock_policies') ? DB::table('stock_policies')->whereIn('scope_key',['global','s:'.$shop,'p:'.$product,'s:'.$shop.':p:'.$product])->get()->map(fn($r)=>(array)$r)->all() : [];
        $indexed=array_column($rows,null,'scope_key');
        foreach (['global','s:'.$shop,'p:'.$product,'s:'.$shop.':p:'.$product] as $scope) {
            foreach (self::FIELDS as $field) if (isset($indexed[$scope][$field])) {
                $result[$field]=in_array($field,['minimum','optimal','maximum'],true)?(string)$indexed[$scope][$field]:(bool)$indexed[$scope][$field];
                $origins[$field]=$scope;
            }
        }
        $result['origins']=$origins;
        return $result;
    }

    public function classify(string $quantity, array $policy, ?string $daily = null, ?int $delay = null): array
    {
        $q=BigDecimal::of($quantity);
        $state=$q->isLessThanOrEqualTo('0')?'out':($q->isLessThanOrEqualTo($policy['minimum'])?'low':(($policy['maximum_enabled'] && $policy['maximum']!==null && $q->isGreaterThan($policy['maximum']))?'over':'normal'));
        $critical=$daily!==null && $delay!==null && BigDecimal::of($daily)->isPositive() && $q->isLessThan(BigDecimal::of($daily)->multipliedBy($delay));
        return ['state'=>$state,'critical'=>$critical,'quantity'=>$quantity,'policy'=>$policy];
    }

    public function save(?int $shop, ?int $product, array $values, int $actor, string $reason): void
    {
        DB::transaction(function () use ($shop,$product,$values,$actor,$reason) {
            \App\Support\UserAdministration::lock();
            $key=$shop?('s:'.$shop.($product?':p:'.$product:'')):($product?'p:'.$product:'global');
            $before=DB::table('stock_policies')->where('scope_key',$key)->lockForUpdate()->first();
            $data=['scope_key'=>$key,'point_of_sale_id'=>$shop,'product_id'=>$product,'updated_by'=>$actor,'updated_at'=>now('UTC')];
            foreach (self::FIELDS as $f) {
                $data[$f]=$values[$f]??null;
                if ($data[$f]!==null && in_array($f,['minimum','optimal','maximum'],true)) {
                    $v=BigDecimal::of($data[$f]);
                    if ($v->isNegative() || $v->isGreaterThan('99999999999999.999999') || $v->getScale()>6) throw ValidationException::withMessages([$f=>__('replenishment.invalid_policy')]);
                    $data[$f]=(string)$v;
                }
            }
            DB::table('stock_policies')->updateOrInsert(['scope_key'=>$key],$data+['created_at'=>$before?->created_at??now('UTC')]);
            $rows=DB::table('stock_policies')->get()->map(fn($r)=>(array)$r)->all();
            $shops=array_unique([0,...array_filter(array_column($rows,'point_of_sale_id'))]);
            $products=array_unique([0,...array_filter(array_column($rows,'product_id'))]);
            if(count($shops)*count($products)>100000) throw ValidationException::withMessages(['policy'=>__('replenishment.policy_volume')]);
            foreach($shops as $s) foreach($products as $p) {
                $e=$this->effective((int)$s,(int)$p,$rows);
                if(BigDecimal::of($e['minimum'])->isGreaterThan($e['optimal']) || ($e['maximum_enabled'] && ($e['maximum']===null || BigDecimal::of($e['optimal'])->isGreaterThan($e['maximum']))))
                    throw ValidationException::withMessages(['policy'=>__('replenishment.invalid_policy')]);
            }
            app(ReplenishmentAudit::class)->record('stock.policy_updated',$key,$shop,(array)$before,$data,$reason,$actor);
        },3);
    }
}

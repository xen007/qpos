<?php
namespace App\Services;

use App\Support\ReportFilter;
use Brick\Math\{BigDecimal,RoundingMode};

final class StatisticsService
{
    public function analyze(ReportFilter $filter,string $mode='previous',?array $current=null): array
    {
        $reports=app(ReportingService::class);
        $previous=clone $filter;
        $previous->period='custom';$previous->cutoff=null;
        if($mode==='year'){
            $previous->start=$filter->start->subYearNoOverflow();
            $previous->end=$filter->end->subDay()->subYearNoOverflow()->addDay();
        }else{
            $days=(int)$filter->start->diffInDays($filter->end);
            $previous->end=$filter->start;$previous->start=$filter->start->subDays($days);
        }
        $current??=$reports->summary($filter);
        $baseline=$reports->summary($previous);
        $comparison=[];
        foreach(['net_sales','cogs','gross_margin','expenses','management_result','sales_count'] as $key){
            $now=$current[$key]??null;$before=$baseline[$key]??null;
            $delta=$now===null||$before===null?null:(string)BigDecimal::of($now)->minus($before);
            $percent=$delta===null||BigDecimal::of($before)->isZero()?null:(string)BigDecimal::of($delta)->multipliedBy(100)->dividedBy(BigDecimal::of($before)->abs(),2,RoundingMode::HalfUp);
            $comparison[]=['key'=>$key,'current'=>$now,'previous'=>$before,'delta'=>$delta,'percent'=>$percent];
        }
        $products=$reports->report($filter,'product')['query'];
        $top=(clone $products)->orderByDesc('net_sales')->orderBy('label')->limit(5)->get()->map(fn($row)=>(array)$row)->all();
        $flop=(clone $products)->orderBy('net_sales')->orderBy('label')->limit(5)->get()->map(fn($row)=>(array)$row)->all();
        $daily=$this->daily($filter,$reports->daily($filter));
        $previousDaily=$this->daily($previous,$reports->daily($previous));
        if($mode==='year'){
            $priorValues=array_column($previousDaily,'value','label');
            $previousDaily=array_map(function($row)use($priorValues){$date=\Carbon\CarbonImmutable::parse($row['label'],'Africa/Douala')->subYearNoOverflow()->toDateString();return ['label'=>$date,'value'=>$priorValues[$date]??'0'];},$daily);
        }
        return [
            'comparison'=>$comparison,'previous_filter'=>$previous,'mode'=>$mode,'top'=>$top,'flop'=>$flop,
            'daily'=>$daily,'previous_daily'=>$previousDaily,
            'incomplete_cost'=>$current['incomplete_cost']||$baseline['incomplete_cost'],
            'period_in_progress'=>$filter->end->greaterThan(now('Africa/Douala')),
        ];
    }

    private function daily(ReportFilter $filter,array $rows): array
    {
        $values=array_column($rows,'value','label');$result=[];
        for($day=$filter->start;$day->lessThan($filter->end);$day=$day->addDay())$result[]=['label'=>$day->toDateString(),'value'=>(string)($values[$day->toDateString()]??'0')];
        return $result;
    }
}

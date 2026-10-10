<?php
namespace App\Services;
use App\Support\ReportFilter;
use Illuminate\Support\Facades\{DB,Schema};

final class PaymentReportService
{
    public function report(ReportFilter $f): array
    {
        $seller=Schema::hasColumn('orders','prepared_by_user_id')?'COALESCE(o.prepared_by_user_id,o.user_id)':'o.user_id';
        $native=DB::table('payment_allocations as a')->join('payments as p','p.id','=','a.payment_id')->join('orders as o','o.id','=','a.order_id')
            ->whereIn('p.point_of_sale_id',$f->shops)->where('p.currency_code','XAF')->whereNotNull('p.receipt_snapshot')->where('p.provenance','live')->where('o.sale_state','<>','legacy');
        $f->limit($native,'p.occurred_at');
        if($f->seller) $native->whereRaw($seller.' = ?',[$f->seller]);
        if($f->cashier) $native->where('p.user_id',$f->cashier);
        if($f->session) $native->where('p.cash_session_id',$f->session);
        $native->selectRaw("p.id as payment_id, o.id as order_id, p.method, p.direction, a.amount, CASE WHEN p.method='transfer' THEN 'internal' WHEN p.direction='outgoing' THEN 'refund' WHEN JSON_UNQUOTE(JSON_EXTRACT(p.receipt_snapshot,'$.flow_kind'))='debt_collection' OR p.idempotency_key NOT LIKE CONCAT('sale-',o.id,'-%') THEN 'debt' ELSE 'sale' END as kind");
        $supplier=DB::table('payments as p')->whereIn('p.point_of_sale_id',$f->shops)->where('p.currency_code','XAF')->whereNotNull('p.supplier_id')->whereNull('p.receipt_snapshot')->whereIn('p.provenance',['live','payment_reversal'])
            ->where('p.occurred_at','>=',$f->start->format('Y-m-d H:i:s'))->where('p.occurred_at','<',$f->end->format('Y-m-d H:i:s'));
        if($f->cutoff) $supplier->where('p.occurred_at','<=',\Carbon\CarbonImmutable::parse($f->cutoff,'UTC')->timezone('Africa/Douala')->format('Y-m-d H:i:s'));
        if($f->seller) $supplier->whereRaw('1=0');
        if($f->cashier) $supplier->where('p.user_id',$f->cashier);
        if($f->session) $supplier->where('p.cash_session_id',$f->session);
        $supplier->selectRaw("p.id as payment_id, NULL as order_id, p.method, p.direction, p.net_amount as amount, CASE WHEN p.method='transfer' THEN 'internal' ELSE 'supplier' END as kind");
        $expense=DB::table('expenses as e')->whereIn('e.point_of_sale_id',$f->shops)->where('e.currency_code','XAF');$f->limit($expense,'e.occurred_at');
        if($f->seller) $expense->whereRaw('1=0');
        if($f->cashier) $expense->where('e.user_id',$f->cashier);
        if($f->session) $expense->where('e.cash_session_id',$f->session);
        $expense->selectRaw("NULL as payment_id, NULL as order_id, e.method, 'outgoing' as direction, e.amount, 'expense' as kind");
        $q=DB::query()->fromSub($native->unionAll($supplier)->unionAll($expense),'f')->select('method')
            ->selectRaw("COUNT(DISTINCT CASE WHEN kind='sale' THEN order_id END) as sales_count, COUNT(DISTINCT CASE WHEN kind='sale' THEN payment_id END) as allocations_count");
        $conditions=['sales_received'=>"kind='sale' AND direction='incoming'",'debt_received'=>"kind='debt' AND direction='incoming'",'refunds'=>"kind='refund'",'supplier_out'=>"kind='supplier' AND direction='outgoing'",'supplier_in'=>"kind='supplier' AND direction='incoming'",'expenses'=>"kind='expense'",'internal_in'=>"kind='internal' AND direction='incoming'",'internal_out'=>"kind='internal' AND direction='outgoing'"];
        foreach($conditions as $col=>$condition) $q->selectRaw('SUM(CASE WHEN '.$condition.' THEN amount ELSE 0 END) as '.$col);
        $q->selectRaw("SUM(CASE WHEN direction='incoming' THEN amount ELSE -amount END) as net_flows")->groupBy('method')->orderBy('method');
        $orders=DB::table('orders as o')->whereIn('o.point_of_sale_id',$f->shops)->where('o.currency_code','XAF')->where('o.sale_state','<>','legacy');$f->limit($orders,'o.created_at');
        if($f->seller) $orders->whereRaw($seller.' = ?',[$f->seller]);
        if($f->cashier) $orders->where('o.user_id',$f->cashier);
        if($f->session) $orders->where('o.cash_session_id',$f->session);
        return ['query'=>$q,'columns'=>['method','sales_count','allocations_count',...array_keys($conditions),'net_flows'],'unique_sales'=>$orders->count(),'flow_notice'=>true];
    }
}

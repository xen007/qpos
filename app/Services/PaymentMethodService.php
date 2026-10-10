<?php
namespace App\Services;

use App\Support\SaleOperation as Op;
use Illuminate\Support\Facades\{DB, Schema};

final class PaymentMethodService
{
    public const LABELS=['cash'=>'Cash','card'=>'External card','bank_transfer'=>'Bank transfer','orange_money'=>'Orange Money','mtn_momo'=>'MTN Mobile Money','wave'=>'Wave','cheque'=>'Cheque'];
    public function choices(int $shop): array
    {
        if (!Schema::hasTable('payment_methods')) return ['cash'=>'Cash','card'=>'External card','bank_transfer'=>'Bank transfer'];
        return DB::table('payment_methods as m')->leftJoin('point_of_sale_payment_method as s',fn($q)=>$q->on('s.payment_method_id','=','m.id')->where('s.point_of_sale_id',$shop))
            ->where('m.sale_enabled',true)->whereRaw('COALESCE(s.is_active,m.default_active) = 1')->orderBy('m.id')->pluck('m.label','m.code')->all();
    }
    public function validate(int $shop, string $method, ?string $reference, bool $originalRefund=false): void
    {
        $allowed=$originalRefund ? self::LABELS : $this->choices($shop);
        if (!isset($allowed[$method])) Op::fail('method','This payment method is not enabled in this store.');
        if ($method!=='cash' && (!is_string($reference) || trim($reference)==='' || mb_strlen($reference)>128 || preg_match('/[\x00-\x1f\x7f]/',$reference))) Op::fail('external_reference','Enter a valid external payment reference.');
    }
}

<?php
namespace App\Support;
use App\Services\ShopWorkflowSettings;
final class ReceiptPrinting
{
    public static function requested(?int $shop):bool
    {
        $r=request();
        $nonce=$r->query('print_nonce');
        if(!is_string($nonce)||!preg_match('/\A[a-zA-Z0-9-]{8,80}\z/D',$nonce))return false;
        return $r->query('print')==='1' || ($r->query('auto_print')==='1' && $shop && (bool)app(ShopWorkflowSettings::class)->get($shop)['receipt_auto_print']);
    }
}

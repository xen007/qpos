<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
final class ShopWorkflowSettings
{
    public function get(int $shop): array
    {
        $defaults=['pending_sale_enabled'=>false,'pending_expiry_minutes'=>240,'taken_lease_minutes'=>10,'orphan_idle_minutes'=>720];
        return Schema::hasTable('point_of_sale_settings') ? array_replace($defaults,(array)(DB::table('point_of_sale_settings')->where('point_of_sale_id',$shop)->first()??[])) : $defaults;
    }
}

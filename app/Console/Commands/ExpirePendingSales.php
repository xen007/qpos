<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Schema};
final class ExpirePendingSales extends Command
{
    protected $signature='sales:expire-pending';
    protected $description='Expire pending sales and release abandoned cashier claims.';
    public function handle(): int
    {
        if(Schema::hasTable('pending_sales')) foreach(DB::table('pending_sales')->whereIn('state',['pending','taken'])->distinct()->pluck('point_of_sale_id') as $shop)app(\App\Services\PendingSaleService::class)->refresh($shop);
        return self::SUCCESS;
    }
}

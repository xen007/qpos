<?php

namespace App\Console\Commands;

use App\Models\PointOfSale;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Brick\Math\BigDecimal;
use RuntimeException;

class ConvertAutomaticOpeningStock extends Command
{
    protected $signature = 'qpos:automatic-opening-lots {--expected-db=} {--apply}';
    protected $description = 'Preflight or convert the confirmed MAIN opening balance to traceable automatic lots.';

    public function handle(StockService $stock): int
    {
        $expected = (string) $this->option('expected-db');
        $actual = DB::connection()->getDatabaseName();
        if ($expected === '' || !hash_equals($expected, $actual)) {
            $this->error('Database target mismatch; no conversion performed.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('product_batch_amendments')) {
            $this->error('Automatic lot migration is not applied.');
            return self::FAILURE;
        }
        $main = PointOfSale::query()->whereKey(1)->where('is_active', true)->first();
        if (!$main) { $this->error('MAIN (ID 1) is missing or inactive.'); return self::FAILURE; }

        $opening = ProductStock::query()->where('point_of_sale_id', 1)->where('unallocated_opening_quantity', '>', 0)
            ->orderBy('product_id')->get();
        $openingTotal = (string) $opening->reduce(fn (BigDecimal $sum, $row) => $sum->plus((string) $row->unallocated_opening_quantity), BigDecimal::zero())->toScale(6);
        $autoLots = ProductBatch::query()->where('provenance', 'auto_opening')->count();
        $convertedMovements = DB::table('stock_movements')->where('type', 'opening_approved')
            ->where('correlation_key', 'like', 'auto-opening-v1-%')->count();
        $otherShops = ProductStock::query()->where('point_of_sale_id', '<>', 1)
            ->where(fn ($q) => $q->where('saleable_quantity', '<>', 0)->orWhere('unsaleable_quantity', '<>', 0)
                ->orWhere('in_transit_quantity', '<>', 0)->orWhere('unallocated_opening_quantity', '<>', 0))->exists();
        $basePhysical = ProductStock::query()->where('point_of_sale_id', 1)->where(fn ($q) => $q
            ->where('saleable_quantity', '<>', 0)->orWhere('unsaleable_quantity', '<>', 0)->orWhere('in_transit_quantity', '<>', 0))->exists();
        $legacy = (string) DB::table('products')->sum('quantity');
        $movementCount = DB::table('stock_movements')->where('type', 'opening')->where('bucket', 'unallocated_opening')->count();
        $this->line(json_encode([
            'database' => $actual, 'products' => DB::table('products')->count(), 'legacy_quantity' => $legacy,
            'opening_remaining' => $openingTotal, 'opening_products' => $opening->count(),
            'opening_movements' => $movementCount, 'automatic_lots' => $autoLots,
            'automatic_conversion_movements' => $convertedMovements,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        if ($otherShops) throw new RuntimeException('Non-MAIN balances exist; conversion refused.');
        if ($autoLots > 0) {
            if ($opening->isNotEmpty() || $autoLots !== DB::table('stock_opening_sources')->where('source_quantity', '>', 0)->count()
                || $convertedMovements !== $autoLots * 2) throw new RuntimeException('Automatic conversion is partial or does not reconcile.');
            $autoProducts=ProductBatch::query()->where('provenance','auto_opening')->distinct()->pluck('product_id');
            foreach ($autoProducts as $productId) {
                $expected=(string)ProductStock::query()->where('point_of_sale_id',1)->where('product_id',$productId)->value('saleable_quantity');
                if ($expected !== $stock->available(1,(int)$productId)) throw new RuntimeException('Auto lot availability does not reconcile for product '.$productId.'.');
            }
            $this->info('Automatic opening conversion already complete; replay is idempotent.');
            return self::SUCCESS;
        }
        if ($basePhysical || $legacy !== '314' || $movementCount !== 23 || $opening->count() !== 23 || $openingTotal !== '314.000000') {
            throw new RuntimeException('Source stock does not match the confirmed 314-unit MAIN opening.');
        }
        if (!$this->option('apply')) { $this->info('Preflight passed; no stock was changed. Use --apply on this exact database after backup.'); return self::SUCCESS; }

        DB::transaction(function () use ($stock, $opening) {
            foreach ($opening as $row) $stock->convertOpeningToAutomaticLot(1, (int) $row->product_id);
        }, 1);

        $remaining = (string) ProductStock::query()->sum('unallocated_opening_quantity');
        $saleable = (string) ProductStock::query()->sum('saleable_quantity');
        $batchSaleable = (string) DB::table('batch_stock')->join('product_batches','product_batches.id','=','batch_stock.product_batch_id')
            ->where('batch_stock.point_of_sale_id',1)->where('product_batches.provenance','auto_opening')->sum('batch_stock.saleable_quantity');
        $lots = ProductBatch::query()->where('provenance', 'auto_opening')->count();
        if ($remaining !== '0.000000' || $saleable !== '314.000000' || $batchSaleable !== '314.000000' || $lots !== 23) {
            throw new RuntimeException('Post-conversion reconciliation failed. Do not reopen the source.');
        }
        $this->info('Converted 314 units into 23 visible automatic opening lots.');
        return self::SUCCESS;
    }
}

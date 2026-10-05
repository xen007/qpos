<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PointOfSale;
use App\Services\CatalogueFingerprint;
use App\Services\StockService;
use App\Support\QuantityDecimal;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Controlled opening only. Activation of consumers is a separate cutover. */
class ConvertStockOpening extends Command
{
    protected $signature = 'qpos:stock-opening {--backup=} {--mapping= : JSON with explicit per-product shop allocations and evidence} {--database= : Verified restored copy only} {--apply}';
    protected $description = 'Preview and record legacy stock once, preserving unknown provenance as blocked opening stock.';
    private const MIGRATION = 'database/migrations/2026_10_05_210000_create_stock_opening_tracking.php';
    private const STOCK_TABLES = ['product_stock','product_batches','batch_stock','stock_movements','order_stock_allocations','stock_conversion_runs','stock_opening_sources'];

    public function handle(CatalogueFingerprint $fingerprint, StockService $stock): int
    {
        try {
            return $this->convert($fingerprint, $stock);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $messages) { foreach ($messages as $message) { $this->error($message); } }
            return self::FAILURE;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function convert(CatalogueFingerprint $fingerprint, StockService $stock): int
    {
        $directory = rtrim((string) $this->option('backup'), '/\\');
        if (!is_file($directory.'/manifest.json')) { throw new RuntimeException('A verified backup manifest is required.'); }
        $manifest = json_decode(file_get_contents($directory.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        if (empty($manifest['restoration_verified']) || empty($manifest['verified_at'])
            || \Carbon\CarbonImmutable::parse($manifest['verified_at'])->lt(now()->subDay())
            || !is_file($directory.'/database.sql') || !is_file($directory.'/files.zip')
            || !hash_equals($manifest['database_sha256'], hash_file('sha256', $directory.'/database.sql'))
            || !hash_equals($manifest['files_archive_sha256'], hash_file('sha256', $directory.'/files.zip'))) {
            throw new RuntimeException('Backup is stale or its restoration/integrity proof is invalid.');
        }
        $probe = $this->option('database');
        if ($probe) {
            if ($probe !== $manifest['probe_database'] || $probe === $manifest['source_database']
                || !preg_match('/\Aqpos_phase2_probe_[A-Za-z0-9_]+\z/D', $probe)) {
                throw new RuntimeException('Only this backup\'s isolated restored copy is allowed.');
            }
            $default = config('database.default');
            config(['database.connections.'.$default.'.database' => $probe]);
            DB::purge($default);
        } elseif (DB::connection()->getDatabaseName() !== $manifest['source_database']) {
            throw new RuntimeException('The source database differs from the backup.');
        }
        $spec = array_diff_key($manifest['fingerprints'], array_flip(self::STOCK_TABLES));
        if ($fingerprint->snapshot(DB::connection(), $spec) !== $spec) {
            throw new RuntimeException('Legacy data changed since backup; create a fresh backup.');
        }
        $products = Product::query()->orderBy('id')->get();
        $source = $products->map(fn ($p) => ['product_id'=>$p->id, 'quantity'=>(string)$p->quantity,
            'expire_date'=>$p->getRawOriginal('expire_date'), 'allows_fractional'=>$p->allows_fractional])->all();
        $sourceHash = hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));
        $mappingPath = (string) $this->option('mapping');
        if (!is_file($mappingPath)) {
            $this->line(json_encode(['source_sha256'=>$sourceHash, 'products'=>$source,
                'shops'=>PointOfSale::active()->get(['id','code','name'])->toArray(),
                'required_mapping'=>['evidence'=>'Owner-confirmed provenance', 'products'=>[
                    ['product_id'=>'ID', 'allocations'=>[['shop_id'=>'ID','quantity'=>'exact decimal']]],
                ]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if ($this->option('apply')) { throw new RuntimeException('Explicit shop mapping is required; no automatic attribution.'); }
            return self::SUCCESS;
        }
        $mapping = json_decode(file_get_contents($mappingPath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($mapping) || !is_string($mapping['evidence'] ?? null) || trim($mapping['evidence']) === ''
            || strlen($mapping['evidence']) > 255 || !is_array($mapping['products'] ?? null)) {
            throw new RuntimeException('Mapping needs an explicit evidence statement and products array.');
        }
        $mapped = [];
        foreach ($mapping['products'] as $entry) {
            $id = $entry['product_id'] ?? null;
            if (!is_int($id) || isset($mapped[$id]) || !$products->contains('id', $id)
                || !is_array($entry['allocations'] ?? null)) { throw new RuntimeException('Invalid, duplicate or unknown source product.'); }
            $mapped[$id] = $entry['allocations'];
        }
        $total = BigDecimal::zero();
        $allocations = 0;
        foreach ($products as $p) {
            $qty = QuantityDecimal::parse((string)$p->quantity)->toScale(6);
            if ($p->allows_fractional === null) { throw new RuntimeException('Fractional rule is unresolved for product '.$p->id); }
            if (!array_key_exists($p->id, $mapped)) { throw new RuntimeException('Missing explicit mapping for product '.$p->id); }
            $sum = BigDecimal::zero();
            $shops = [];
            foreach ($mapped[$p->id] as $allocation) {
                $shopId = $allocation['shop_id'] ?? null;
                if (!is_int($shopId) || isset($shops[$shopId]) || !PointOfSale::active()->whereKey($shopId)->exists()) {
                    throw new RuntimeException('Invalid, inactive or duplicate destination shop.');
                }
                $amount = QuantityDecimal::parse($allocation['quantity'] ?? null, 'quantity', true)->toScale(6);
                if (!$p->allows_fractional) { $amount->toScale(0); }
                $shops[$shopId] = true;
                $sum = $sum->plus($amount);
                $allocations++;
            }
            if ($sum->compareTo($qty) !== 0) { throw new RuntimeException('Mapping sum differs from legacy stock for product '.$p->id); }
            $total = $total->plus($qty);
        }
        $mappingHash = hash('sha256', json_encode($mapping, JSON_THROW_ON_ERROR));
        $counts = ['products'=>$products->count(), 'movements'=>$allocations, 'quantity'=>(string)$total->toScale(6),
            'bucket'=>'unallocated_opening', 'batches_created'=>0, 'costs_invented'=>0];
        $this->line(json_encode($counts, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        if (!$this->option('apply')) { $this->info('Preflight passed. No opening written.'); return self::SUCCESS; }
        if (!$probe && !app()->isDownForMaintenance()) { throw new RuntimeException('Freeze writers with artisan down before source opening; keep them frozen until 3.C cutover.'); }
        $hashes = [];
        foreach ([__FILE__, app_path('Services/StockService.php'), app_path('Services/CatalogueFingerprint.php'),
            app_path('Support/QuantityDecimal.php'), app_path('Support/MoneyDecimal.php'),
            app_path('Models/ProductStock.php'), app_path('Models/BatchStock.php'), app_path('Models/ProductBatch.php'),
            app_path('Models/StockMovement.php'), app_path('Models/Product.php'), app_path('Models/PointOfSale.php'),
            base_path(self::MIGRATION), base_path('database/migrations/2026_10_05_200000_create_stock_core_tables.php')] as $file) {
            $hashes[basename($file)] = hash_file('sha256', $file);
        }
        $proofPath = $directory.'/stock-opening-probe.json';
        if (!$probe) {
            $proof = is_file($proofPath) ? json_decode(file_get_contents($proofPath), true, 512, JSON_THROW_ON_ERROR) : [];
            if (($proof['database'] ?? null) !== $manifest['probe_database'] || ($proof['source_sha256'] ?? null) !== $sourceHash
                || ($proof['mapping_sha256'] ?? null) !== $mappingHash || ($proof['code_hashes'] ?? null) !== $hashes
                || ($proof['backup_sha256'] ?? null) !== $manifest['database_sha256'] || empty($proof['reconciled'])) {
                throw new RuntimeException('The identical mapping and code must succeed on the restored copy first.');
            }
        }
        if (Artisan::call('migrate', ['--path'=>['database/migrations/2026_10_05_200000_create_stock_core_tables.php', self::MIGRATION], '--force'=>true]) !== 0) {
            throw new RuntimeException('Opening schema preparation failed.');
        }
        $this->line(Artisan::output());
        $runId = DB::transaction(function () use ($stock, $sourceHash, $mappingHash, $manifest, $mapped, $mapping, $counts, $spec, $fingerprint) {
            // The same order as checkout. DDL is deliberately outside this transaction.
            $locked = Product::query()->orderBy('id')->lockForUpdate()->get();
            if ($fingerprint->snapshot(DB::connection(), $spec) !== $spec) { throw new RuntimeException('Legacy data changed during preflight.'); }
            $existing = DB::table('stock_conversion_runs')->orderBy('id')->lockForUpdate()->first();
            if ($existing) {
                if ($existing->status !== 'completed' || $existing->source_sha256 !== $sourceHash || $existing->mapping_sha256 !== $mappingHash) {
                    throw new RuntimeException('An incompatible opening already exists; do not replay legacy stock.');
                }
                $this->reconcile((int)$existing->id);
                return (int)$existing->id;
            }
            if (DB::table('stock_movements')->exists() || DB::table('product_stock')->exists()
                || DB::table('product_batches')->exists() || DB::table('batch_stock')->exists()) {
                throw new RuntimeException('Stock is not empty; opening over an existing ledger is forbidden.');
            }
            $cutoff = now('Africa/Douala')->format('Y-m-d H:i:s');
            $run = DB::table('stock_conversion_runs')->insertGetId(['backup_sha256'=>$manifest['database_sha256'],
                'mapping_sha256'=>$mappingHash, 'source_sha256'=>$sourceHash, 'cutoff_at'=>$cutoff,
                'status'=>'processing', 'created_at'=>$cutoff, 'updated_at'=>$cutoff]);
            foreach ($locked as $p) {
                DB::table('stock_opening_sources')->insert(['conversion_run_id'=>$run, 'product_id'=>$p->id,
                    'source_quantity'=>(string)QuantityDecimal::parse((string)$p->quantity)->toScale(6),
                    'source_expire_date'=>$p->getRawOriginal('expire_date'),
                    'source_values'=>json_encode(['quantity'=>$p->quantity,'expire_date'=>$p->getRawOriginal('expire_date'),
                        'allows_fractional'=>$p->allows_fractional,'evidence'=>$mapping['evidence']], JSON_THROW_ON_ERROR),
                    'allocation'=>json_encode($mapped[$p->id], JSON_THROW_ON_ERROR), 'created_at'=>$cutoff,'updated_at'=>$cutoff]);
                foreach ($mapped[$p->id] as $allocation) {
                    $stock->increase($allocation['shop_id'], $p->id, $allocation['quantity'], [
                        'type'=>'opening', 'bucket'=>'unallocated_opening', 'correlation_key'=>'opening:product:'.$p->id,
                        'conversion_run_id'=>$run, 'occurred_at'=>$cutoff, 'reason'=>trim($mapping['evidence']),
                    ]);
                }
            }
            $this->reconcile($run);
            if ($fingerprint->snapshot(DB::connection(), $spec) !== $spec) { throw new RuntimeException('Legacy data must remain unchanged.'); }
            DB::table('stock_conversion_runs')->where('id',$run)->update(['status'=>'completed','counts'=>json_encode($counts,JSON_THROW_ON_ERROR),'updated_at'=>$cutoff]);
            return $run;
        }, 3);
        $proof = ['database'=>DB::connection()->getDatabaseName(), 'run_id'=>$runId, 'backup_sha256'=>$manifest['database_sha256'],
            'mapping_sha256'=>$mappingHash,'source_sha256'=>$sourceHash,'code_hashes'=>$hashes,'counts'=>$counts,
            'reconciled'=>true,'legacy_unchanged'=>true,'consumers_activated'=>false,'verified_at'=>now('Africa/Douala')->toIso8601String()];
        file_put_contents($probe ? $proofPath : $directory.'/stock-opening-source.json', json_encode($proof,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
        $this->info('Opening reconciled; unknown quantities remain blocked. Consumer activation is pending 3.C.');
        return self::SUCCESS;
    }

    private function reconcile(int $run): void
    {
        foreach (DB::table('stock_opening_sources')->where('conversion_run_id',$run)->get() as $source) {
            $sum = DB::table('stock_movements')->where('conversion_run_id',$run)->where('product_id',$source->product_id)
                ->where('type','opening')->sum('quantity_delta');
            if (BigDecimal::of((string)$sum)->compareTo($source->source_quantity) !== 0) { throw new RuntimeException('Opening/source reconciliation failed.'); }
        }
        foreach (DB::table('product_stock')->get() as $balance) {
            foreach (['saleable','unsaleable','in_transit','unallocated_opening'] as $bucket) {
                $sum = DB::table('stock_movements')->where('point_of_sale_id',$balance->point_of_sale_id)
                    ->where('product_id',$balance->product_id)->where('bucket',$bucket)->sum('quantity_delta');
                if (BigDecimal::of((string)$sum)->compareTo($balance->{$bucket.'_quantity'}) !== 0) { throw new RuntimeException('Journal/balance reconciliation failed.'); }
            }
        }
    }
}

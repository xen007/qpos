<?php
namespace App\Console\Commands;

use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Services\CatalogueFingerprint;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConvertCatalogue extends Command
{
    protected $signature = 'qpos:catalogue-convert {--backup= : Protected verified backup directory} {--database= : Only the manifest probe database is allowed} {--apply : Apply additive conversion}';
    protected $description = 'Preserve legacy catalogue data and convert proven units, prices and discounts with evidence.';

    public function handle(CatalogueFingerprint $fingerprint): int
    {
        $directory = rtrim((string) $this->option('backup'), '/\\');
        $path = $directory.'/manifest.json';
        if (!is_file($path)) { $this->error('A verified fresh backup manifest is required.'); return self::FAILURE; }
        $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (empty($manifest['restoration_verified']) || !hash_equals($manifest['database_sha256'], hash_file('sha256', $directory.'/database.sql'))
            || !hash_equals($manifest['files_archive_sha256'], hash_file('sha256', $directory.'/files.zip'))) {
            $this->error('Backup proof is invalid.'); return self::FAILURE;
        }
        $probe = $this->option('database');
        if ($probe) {
            if ($probe !== $manifest['probe_database'] || !preg_match('/\Aqpos_phase2_probe_[A-Za-z0-9_]+\z/D', $probe)) {
                $this->error('Only the verified isolated probe database may override the connection.'); return self::FAILURE;
            }
            $default = config('database.default');
            config(['database.connections.'.$default.'.database' => $probe]);
            DB::purge($default);
        } elseif (!app()->isDownForMaintenance()) {
            $this->error('Freeze application writers with artisan down before source conversion.'); return self::FAILURE;
        }
        $connection = DB::connection();
        if (!$probe && $connection->getDatabaseName() !== $manifest['source_database']) {
            $this->error('Source database differs from the backup.'); return self::FAILURE;
        }
        if ($fingerprint->snapshot($connection, $manifest['fingerprints']) !== $manifest['fingerprints']) {
            $this->error('Business data changed since backup; create a new backup.'); return self::FAILURE;
        }
        if (!$this->option('apply')) { $this->info('Preflight passed; no data changed.'); return self::SUCCESS; }
        if (!$probe) {
            $proofPath = $directory.'/conversion-probe.json';
            $proof = is_file($proofPath) ? json_decode(file_get_contents($proofPath), true, 512, JSON_THROW_ON_ERROR) : [];
            if (empty($proof['legacy_business_unchanged']) || empty($proof['probe'])
                || ($proof['database'] ?? null) !== $manifest['probe_database']
                || ($proof['backup_sha256'] ?? null) !== $manifest['database_sha256']
                || ($proof['converter_sha256'] ?? null) !== hash_file('sha256', __FILE__)) {
                $this->error('Apply and verify this backup on its isolated probe before source conversion.');
                return self::FAILURE;
            }
        }
        $migrationPaths = [
            'database/migrations/2026_10_01_100000_create_points_of_sale_table.php',
            'database/migrations/2026_10_01_100100_create_point_of_sale_user_table.php',
            'database/migrations/2026_10_02_170000_add_catalogue_units.php',
            'database/migrations/2026_10_02_170100_add_shop_preference.php',
            'database/migrations/2026_10_02_170200_add_catalogue_pricing.php',
            'database/migrations/2026_10_02_170300_add_catalogue_conversion_tracking.php',
        ];
        $migrationHashes = [];
        foreach ($migrationPaths as $migrationPath) { $migrationHashes[$migrationPath] = hash_file('sha256', base_path($migrationPath)); }
        if (!$probe && ($proof['migration_hashes'] ?? null) !== $migrationHashes) {
            $this->error('Migration code changed since the isolated conversion; repeat it on the probe.');
            return self::FAILURE;
        }
        if (Artisan::call('migrate', ['--path' => $migrationPaths, '--force' => true]) !== 0) { $this->error('Schema preparation failed.'); return self::FAILURE; }
        $this->line(Artisan::output());
        $runId = (string) Str::uuid();
        $counts = DB::transaction(function () use ($runId, $manifest, $fingerprint, $connection) {
            $now = now();
            DB::table('catalogue_conversion_runs')->insert(['id' => $runId, 'backup_sha256' => $manifest['database_sha256'], 'status' => 'processing', 'created_at' => $now, 'updated_at' => $now]);
            $counts = ['products' => 0, 'references_created' => 0, 'barcodes_created' => 0, 'promotions_created' => 0, 'issues' => 0];
            $issue = function ($id, $kind, $values) use ($runId, $now, &$counts) {
                DB::table('catalogue_conversion_issues')->insert(['run_id' => $runId, 'source_table' => 'products', 'source_id' => $id, 'kind' => $kind,
                    'source_values' => json_encode($values, JSON_THROW_ON_ERROR), 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);
                $counts['issues']++;
            };
            $map = function ($id, $purpose, $targetTable, $targetId, $values) use ($runId, $now) {
                DB::table('catalogue_conversion_mappings')->insertOrIgnore(['run_id' => $runId, 'source_table' => 'products', 'source_id' => $id, 'purpose' => $purpose,
                    'target_table' => $targetTable, 'target_id' => $targetId, 'source_values' => json_encode($values, JSON_THROW_ON_ERROR), 'created_at' => $now, 'updated_at' => $now]);
            };
            $products = DB::table('products')->select('products.*')
                ->selectRaw('CAST(price AS CHAR) AS raw_price, CAST(purchase_price AS CHAR) AS raw_cost, CAST(discount AS CHAR) AS raw_discount')
                ->orderBy('id')->lockForUpdate()->get();
            foreach ($products as $product) {
                $counts['products']++;
                $source = ['unit_id' => $product->unit_id, 'price' => $product->raw_price, 'reference_cost' => $product->raw_cost, 'sku' => $product->sku,
                    'discount' => $product->raw_discount, 'discount_type' => $product->discount_type];
                if ($product->allows_fractional === null) { $issue($product->id, 'fractional_rule_unknown', ['allows_fractional' => null]); }
                $unit = $product->unit_id ? DB::table('units')->where('id', $product->unit_id)->first() : null;
                if (!$unit) { $issue($product->id, 'base_unit_missing', $source); continue; }
                $reference = ProductUnit::where('product_id', $product->id)->where('is_reference', true)->lockForUpdate()->first();
                if ($reference && ((int) $reference->unit_id !== (int) $unit->id || BigDecimal::of($reference->factor)->compareTo('1') !== 0)) {
                    $issue($product->id, 'reference_unit_conflict', $source); continue;
                }
                if (!$reference) {
                    $reference = new ProductUnit(['unit_id' => $unit->id, 'code' => 'BASE', 'label' => $unit->title, 'factor' => '1.000000', 'is_reference' => true, 'is_active' => true]);
                    $reference->product_id = $product->id; $reference->save(); $counts['references_created']++;
                }
                $map($product->id, 'reference', 'product_units', $reference->id, $source);
                foreach (['sale_price_ttc' => $product->raw_price, 'reference_purchase_cost' => $product->raw_cost] as $field => $raw) {
                    if ($reference->{$field} !== null) { continue; }
                    try {
                        if ($raw === null) { throw new \RuntimeException('Unknown source amount.'); }
                        $amount = BigDecimal::of($raw)->toScale(6);
                        if ($amount->isNegative() || $amount->isGreaterThan('99999999999999.999999')) { throw new \RuntimeException('Source amount outside target range.'); }
                        $reference->{$field} = (string) $amount;
                    } catch (\Throwable $exception) {
                        $issue($product->id, 'amount_requires_decision_'.$field, ['source' => $raw, 'target' => null, 'delta' => 'unapproved']);
                    }
                }
                $reference->save();
                $native = [];
                if ($product->catalogue_price_ttc === null && $reference->sale_price_ttc !== null) { $native['catalogue_price_ttc'] = $reference->sale_price_ttc; }
                if ($product->catalogue_reference_cost === null && $reference->reference_purchase_cost !== null) { $native['catalogue_reference_cost'] = $reference->reference_purchase_cost; }
                if ($native) { DB::table('products')->where('id', $product->id)->update($native); }
                if ($product->sku && preg_match('/\A[!-~]{1,128}\z/D', $product->sku)
                    && DB::table('products')->where('sku', $product->sku)->count() === 1) {
                    $barcode = ProductBarcode::where('barcode', $product->sku)->first();
                    if (!$barcode) { $barcode = $reference->barcodes()->create(['barcode' => $product->sku, 'is_active' => true]); $counts['barcodes_created']++; }
                    if ((int) $barcode->product_unit_id === (int) $reference->id) { $map($product->id, 'barcode', 'product_barcodes', $barcode->id, ['sku' => $product->sku]); }
                    else { $issue($product->id, 'barcode_conflict', ['sku' => $product->sku]); }
                } elseif ($product->sku) { $issue($product->id, 'barcode_not_convertible', ['sku' => $product->sku]); }
                try {
                    $discount = BigDecimal::of($product->raw_discount ?? '0')->toScale(6);
                    if ($discount->isNegative()) { throw new \RuntimeException('Negative discount.'); }
                    if ($discount->isPositive() && !DB::table('catalogue_conversion_mappings')->where('source_table', 'products')->where('source_id', $product->id)->where('purpose', 'discount')->exists()) {
                        if (!in_array($product->discount_type, ['fixed', 'percentage'], true) || ($product->discount_type === 'percentage' && $discount->isGreaterThan('100'))
                            || ($product->discount_type === 'fixed' && ($reference->sale_price_ttc === null || $discount->isGreaterThan($reference->sale_price_ttc)))) { throw new \RuntimeException('Invalid discount.'); }
                        $promotion = Promotion::firstOrCreate(['legacy_product_id' => $product->id], ['product_unit_id' => $reference->id, 'name' => 'Legacy: '.$product->name, 'kind' => $product->discount_type,
                            'value' => (string) $discount, 'minimum_quantity' => '0', 'priority' => 0, 'is_active' => (bool) $product->status]);
                        $map($product->id, 'discount', 'promotions', $promotion->id, ['discount' => $product->raw_discount, 'discount_type' => $product->discount_type]);
                        if ($promotion->wasRecentlyCreated) { $counts['promotions_created']++; }
                    }
                } catch (\Illuminate\Database\QueryException $exception) { throw $exception; }
                catch (\Throwable $exception) { $issue($product->id, 'discount_requires_decision', ['discount' => $product->raw_discount, 'discount_type' => $product->discount_type]); }
            }
            if ($fingerprint->snapshot($connection, $manifest['fingerprints']) !== $manifest['fingerprints']) { throw new \RuntimeException('Legacy business fingerprint changed; additive conversion rolled back.'); }
            DB::table('catalogue_conversion_runs')->where('id', $runId)->update(['status' => $counts['issues'] ? 'completed_with_issues' : 'completed', 'counts' => json_encode($counts, JSON_THROW_ON_ERROR), 'completed_at' => $now, 'updated_at' => $now]);
            return $counts;
        });
        foreach ([\Database\Seeders\PointOfSaleSeeder::class, \Database\Seeders\PointOfSalePermissionSeeder::class, \Database\Seeders\PricingPermissionSeeder::class] as $seeder) {
            if (Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]) !== 0) { throw new \RuntimeException('Scoped reference seeding failed.'); }
        }
        $report = ['run_id' => $runId, 'database' => $connection->getDatabaseName(), 'backup_sha256' => $manifest['database_sha256'], 'converter_sha256' => hash_file('sha256', __FILE__), 'migration_hashes' => $migrationHashes, 'counts' => $counts, 'legacy_business_unchanged' => true,
            'completed_at' => now()->toIso8601String(), 'probe' => (bool) $probe];
        file_put_contents($directory.'/conversion-'.($probe ? 'probe' : 'source').'.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info('Additive conversion completed; legacy business fingerprints unchanged.');
        $this->line(json_encode($report, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return self::SUCCESS;
    }
}

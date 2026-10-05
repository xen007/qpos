<?php

namespace App\Console\Commands;

use App\Support\CatalogueSchema;
use App\Support\FractionalQuantityRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClassifyFractionalProducts extends Command
{
    protected $signature = 'qpos:classify-fractional-products {--apply : Save clear rules for products that are still unknown}';
    protected $description = 'Classify legacy products from their base unit and report units needing a decision.';

    public function handle(): int
    {
        if (!CatalogueSchema::ready()) {
            $this->error('Catalogue unit migrations must be applied first.');

            return self::FAILURE;
        }

        $products = DB::table('products')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->whereNull('products.allows_fractional')
            ->select(['products.id', 'products.name', 'products.unit_id', 'units.title as unit_title', 'units.short_name'])
            ->orderBy('products.id')->get();

        $classified = [];
        $ambiguous = [];
        foreach ($products as $product) {
            $rule = $product->unit_id
                ? FractionalQuantityRule::fromLabels($product->unit_title, $product->short_name)
                : null;

            if ($rule === null) {
                $ambiguous[] = $product;
            } else {
                $classified[] = ['id' => $product->id, 'allows_fractional' => $rule];
            }
        }

        $this->line('Clearly classified: '.count($classified).' (integer: '.count(array_filter($classified, fn ($row) => !$row['allows_fractional'])).'; fractional: '.count(array_filter($classified, fn ($row) => $row['allows_fractional'])).').');
        $this->line('Needs administrator review: '.count($ambiguous));
        foreach ($ambiguous as $product) {
            $this->line('Product #'.$product->id.' '.$product->name.' — base unit: '.($product->unit_title ?? 'missing').' ('.($product->short_name ?? 'missing').')');
        }

        if ($this->option('apply')) {
            $updated = DB::transaction(function () use ($classified) {
                $count = 0;
                foreach ($classified as $row) {
                    $count += DB::table('products')->where('id', $row['id'])
                        ->whereNull('allows_fractional')->update(['allows_fractional' => $row['allows_fractional']]);
                }

                return $count;
            });
            $this->info('Applied clear rules to '.$updated.' products; ambiguous values remain unchanged.');
        } else {
            $this->comment('Preview only. Pass --apply to save the clear rules.');
        }

        return self::SUCCESS;
    }
}

<?php

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Rebase the 11 legacy products from Dozen to Piece and retain Dozen at factor 12. */
    public function up(): void
    {
        if (!Schema::hasTable('product_units') || !Schema::hasTable('promotions')
            || !Schema::hasColumn('products', 'allows_fractional')) {
            throw new RuntimeException('Phase 2 catalogue migrations must be applied first.');
        }

        $piece = DB::table('units')->where('short_name', 'pcs')->where('title', 'Piece')->first();
        $dozen = DB::table('units')->where('short_name', 'dz')->where('title', 'Dozen')->first();
        if (!$piece || !$dozen || !$piece->is_active || !$dozen->is_active) {
            throw new RuntimeException('Active Piece (pcs) and Dozen (dz) units are required.');
        }

        $products = DB::table('products')->where('unit_id', $dozen->id)->orderBy('id')->get();
        if ($products->count() !== 11) {
            throw new RuntimeException('Expected exactly 11 products with Dozen as base unit; no data was changed.');
        }

        $productIds = $products->pluck('id')->all();
        $references = DB::table('product_units')->whereIn('product_id', $productIds)
            ->where('is_reference', true)->get()->keyBy('product_id');
        if ($references->count() !== 11
            || DB::table('product_units')->whereIn('product_id', $productIds)->count() !== 11) {
            throw new RuntimeException('Expected one reference packaging per product and no existing extra packagings.');
        }

        if (DB::table('order_products')->whereIn('product_id', $productIds)->whereNotNull('product_unit_id')->exists()
            || DB::table('price_rules')->whereIn('product_unit_id', $references->pluck('id'))->exists()) {
            throw new RuntimeException('Existing unit-based sales or price rules require a separate reviewed conversion.');
        }

        foreach ($products as $product) {
            $reference = $references->get($product->id);
            if (!$reference || (int) $reference->unit_id !== (int) $dozen->id
                || BigDecimal::of($reference->factor)->compareTo('1') !== 0
                || $reference->sale_price_ttc === null || $reference->reference_purchase_cost === null) {
                throw new RuntimeException("Product {$product->id} has an incomplete or conflicting reference price.");
            }
            if (DB::table('product_units')->where('product_id', $product->id)->where('code', 'DOZEN')->exists()) {
                throw new RuntimeException("Product {$product->id} already has a DOZEN packaging.");
            }
            $promotion = DB::table('promotions')->where('legacy_product_id', $product->id)->first();
            if ((float) ($product->discount ?? 0) > 0 && (!$promotion || !in_array($promotion->kind, ['fixed', 'percentage'], true))) {
                throw new RuntimeException("Product {$product->id} has a discount that cannot be converted safely.");
            }
        }

        DB::transaction(function () use ($products, $piece, $dozen, $references): void {
            $now = now();
            foreach ($products as $product) {
                $reference = $references->get($product->id);
                $oldSale = BigDecimal::of($reference->sale_price_ttc)->toScale(6);
                $oldCost = BigDecimal::of($reference->reference_purchase_cost)->toScale(6);
                $baseSale = $this->perPiece($oldSale);
                $baseCost = $this->perPiece($oldCost);
                $quantity = (int) $product->quantity;
                if ($quantity > intdiv(PHP_INT_MAX, 12)) {
                    throw new RuntimeException("Product {$product->id} stock exceeds the supported range.");
                }

                $discountKind = $product->catalogue_discount_type ?? $product->discount_type;
                DB::table('products')->where('id', $product->id)->update([
                    'unit_id' => $piece->id,
                    'allows_fractional' => false,
                    'quantity' => $quantity * 12,
                    'price' => (string) $baseSale->toScale(2, RoundingMode::HALF_UP),
                    'purchase_price' => (string) $baseCost->toScale(2, RoundingMode::HALF_UP),
                    'catalogue_price_ttc' => (string) $baseSale,
                    'catalogue_reference_cost' => (string) $baseCost,
                    'discount' => $this->legacyDiscount($product->discount, $product->discount_type),
                    'catalogue_discount' => $this->baseDiscount(
                        $product->catalogue_discount ?? $product->discount,
                        $discountKind,
                    ),
                    'catalogue_discount_type' => $discountKind,
                    'updated_at' => $now,
                ]);

                DB::table('product_units')->where('id', $reference->id)->update([
                    'unit_id' => $piece->id,
                    'label' => $piece->title,
                    'factor' => '1.000000',
                    'sale_price_ttc' => (string) $baseSale,
                    'reference_purchase_cost' => (string) $baseCost,
                    'updated_at' => $now,
                ]);

                $dozenUnitId = DB::table('product_units')->insertGetId([
                    'product_id' => $product->id,
                    'unit_id' => $dozen->id,
                    'code' => 'DOZEN',
                    'label' => $dozen->title,
                    'factor' => '12.000000',
                    'is_reference' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'sale_price_ttc' => (string) $oldSale,
                    'reference_purchase_cost' => (string) $oldCost,
                ]);

                $legacyPromotion = DB::table('promotions')->where('legacy_product_id', $product->id)->first();
                if ($legacyPromotion) {
                    $baseValue = $legacyPromotion->kind === 'fixed'
                        ? $this->perPiece(BigDecimal::of($legacyPromotion->value))
                        : BigDecimal::of($legacyPromotion->value)->toScale(6);
                    DB::table('promotions')->where('id', $legacyPromotion->id)->update([
                        'value' => (string) $baseValue,
                        'minimum_quantity' => (string) BigDecimal::of($legacyPromotion->minimum_quantity)->multipliedBy(12)->toScale(6),
                        'buy_quantity' => $legacyPromotion->buy_quantity === null ? null : $legacyPromotion->buy_quantity * 12,
                        'free_quantity' => $legacyPromotion->free_quantity === null ? null : $legacyPromotion->free_quantity * 12,
                        'bundle_quantity' => $legacyPromotion->bundle_quantity === null ? null : $legacyPromotion->bundle_quantity * 12,
                        'updated_at' => $now,
                    ]);

                    $copy = (array) $legacyPromotion;
                    unset($copy['id']);
                    $copy['product_unit_id'] = $dozenUnitId;
                    $copy['legacy_product_id'] = null;
                    $copy['name'] = $legacyPromotion->name.' — Dozen';
                    $copy['created_at'] = $now;
                    $copy['updated_at'] = $now;
                    DB::table('promotions')->insert($copy);
                }
            }
        });
    }

    private function perPiece(BigDecimal $amount): BigDecimal
    {
        return $amount->dividedBy('12', 6, RoundingMode::HALF_UP)->toScale(6);
    }

    private function legacyDiscount(mixed $discount, ?string $kind): mixed
    {
        if ($discount === null || $kind !== 'fixed') {
            return $discount;
        }

        return (string) $this->perPiece(BigDecimal::of((string) $discount))->toScale(2, RoundingMode::HALF_UP);
    }

    private function baseDiscount(mixed $discount, ?string $kind): ?string
    {
        if ($discount === null) {
            return null;
        }

        return (string) ($kind === 'fixed'
            ? $this->perPiece(BigDecimal::of((string) $discount))
            : BigDecimal::of((string) $discount)->toScale(6));
    }

    public function down(): void
    {
        throw new RuntimeException('This unit conversion is data-sensitive; restore the verified pre-migration backup instead of rolling it back.');
    }
};

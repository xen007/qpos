<?php
namespace App\Support;
use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CatalogueCodes
{
    public static function transaction(callable $work): mixed
    {
        if (!CatalogueSchema::ready() || DB::getDriverName() !== 'mysql') { return DB::transaction($work); }
        $key = 'qpos-catalogue-codes-'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 32);
        $lock = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$key]);
        if ((int) $lock->acquired !== 1) {
            throw ValidationException::withMessages(['sku' => __('Catalogue codes are busy; try again.')]);
        }
        try { return DB::transaction($work); }
        finally { DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$key]); }
    }
    public static function validateSku(string $sku, ?int $productId = null): void
    {
        if (Product::where('sku', $sku)->when($productId, fn ($q) => $q->whereKeyNot($productId))->exists()) {
            throw ValidationException::withMessages(['sku' => __('This barcode is already in use.')]);
        }
        if (!CatalogueSchema::ready()) { return; }
        $barcode = ProductBarcode::with('productUnit')->where('barcode', $sku)->first();
        if ($barcode && (!$barcode->productUnit->is_reference || (int) $barcode->productUnit->product_id !== $productId)) {
            throw ValidationException::withMessages(['sku' => __('This barcode is already in use.')]);
        }
    }
}

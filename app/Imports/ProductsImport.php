<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Unit;
use App\Services\ProductUnitService;
use App\Support\CatalogueSchema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(
        private readonly ?int $supplierId,
        private readonly int $userId,
        private readonly int $shopId,
    ) {
    }

    public function model(array $row)
    {
        $row += ['brand' => null, 'category' => null, 'unit' => null, 'sku' => null,
            'purchase_price' => 0, 'quantity' => 0, 'discount' => 0, 'discount_type' => 'fixed', 'status' => true];
        foreach (['purchase_price', 'quantity', 'discount', 'discount_type', 'status'] as $field) {
            if ($row[$field] === null || $row[$field] === '') {
                $row[$field] = match ($field) { 'discount_type' => 'fixed', 'status' => true, default => 0 };
            }
        }
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:fixed,percentage'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $validator->after(function ($validator) use ($row) {
            $price = (float) ($row['price'] ?? 0);
            $discount = (float) ($row['discount'] ?? 0);
            $discountType = $row['discount_type'] ?? null;

            if ($discountType === 'percentage' && $discount > 100) {
                $validator->errors()->add('discount', __('A percentage discount cannot exceed 100.'));
            }
            if ($discountType === 'fixed' && $discount > $price) {
                $validator->errors()->add('discount', __('A fixed discount cannot exceed the product price.'));
            }

            $purchaseTotal = round((float) ($row['purchase_price'] ?? 0) * (int) ($row['quantity'] ?? 0), 2);
            if ($purchaseTotal > 99999999.99) {
                $validator->errors()->add('quantity', __('The purchase total exceeds the supported limit.'));
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (\App\Support\PricingSchema::ready()) {
            foreach (['price', 'purchase_price', 'discount'] as $field) {
                $row[$field] = (string) \App\Support\MoneyDecimal::parse((string) $row[$field], $field);
            }
            $price = \Brick\Math\BigDecimal::of($row['price']);
            $discount = \Brick\Math\BigDecimal::of($row['discount']);
            if ($row['discount_type'] === 'percentage' && $discount->isGreaterThan('100')) {
                throw ValidationException::withMessages(['discount' => __('A percentage discount cannot exceed 100.')]);
            }
            if ($row['discount_type'] === 'fixed' && $discount->isGreaterThan($price)) {
                throw ValidationException::withMessages(['discount' => __('A fixed discount cannot exceed the product price.')]);
            }
            if ((int) $row['quantity'] > 0 && !\Brick\Math\BigDecimal::of($row['purchase_price'])->isEqualTo(\Brick\Math\BigDecimal::of($row['purchase_price'])->toScale(2, \Brick\Math\RoundingMode::HalfUp))) {
                throw ValidationException::withMessages(['purchase_price' => __('Legacy purchase receipts support two decimals; use a reference cost with two decimals until Phase 3.')]);
            }
        }

        $brandName = trim((string) $row['brand']);
        $categoryName = trim((string) $row['category']);
        $unitName = trim((string) $row['unit']);
        $brand = $brandName !== '' ? Brand::firstOrCreate(['name' => $brandName]) : null;
        $category = $categoryName !== '' ? Category::firstOrCreate(['name' => $categoryName]) : null;
        $unit = null;
        if ($unitName !== '') {
            if (Unit::where('title', $unitName)->count() > 1) {
                throw ValidationException::withMessages(['unit' => __('The unit name is ambiguous; select an existing unit explicitly.')]);
            }
            $unit = Unit::firstOrCreate(['title' => $unitName], ['short_name' => $unitName]);
            if (CatalogueSchema::ready() && !$unit->is_active) {
                throw ValidationException::withMessages(['unit' => __('Select an active unit.')]);
            }
        }

        $originalSku = trim((string) $row['sku']) ?: 'P-'.Str::uuid();
        $sku = $originalSku;
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter++;
        }

        if (CatalogueSchema::ready() && \App\Models\ProductBarcode::where('barcode', $sku)->exists()) {
            throw ValidationException::withMessages(['sku' => __('This barcode is already in use.')]);
        }

        $product = Product::create([
            'name' => trim($row['name']),
            'sku' => $sku,
            'description' => $row['description'] ?? null,
            'category_id' => $category?->id,
            'brand_id' => $brand?->id,
            'unit_id' => $unit?->id,
            'price' => round((float) $row['price'], 2),
            'discount' => round((float) ($row['discount'] ?? 0), 2),
            'discount_type' => $row['discount_type'],
            'purchase_price' => round((float) $row['purchase_price'], 2),
            'quantity' => 0,
            'expire_date' => $row['expire_date'] ?? null,
            'status' => (bool) $row['status'],
        ]);

        if (CatalogueSchema::ready()) {
            $fractionalRule = \App\Support\FractionalQuantityRule::classify($unit);
            $product->update(['allows_fractional' => $fractionalRule]);
            if ($unit && $fractionalRule !== null) {
                app(ProductUnitService::class)->save($product, [
                    'unit_id' => $unit->id, 'code' => 'BASE', 'label' => $unit->title, 'factor' => '1', 'is_active' => true,
                ]);
            }
        }
        app(\App\Services\ReferencePricingService::class)->sync($product, [
            'price' => (string) $row['price'], 'purchase_price' => (string) $row['purchase_price'],
            'discount' => (string) $row['discount'], 'discount_type' => $row['discount_type'],
        ], true);
        if ((int) $row['quantity'] === 0) { return null; }
        abort_unless(\App\Models\User::findOrFail($this->userId)->can('purchase_receive'),403);
        if (!$this->supplierId) { throw ValidationException::withMessages(['quantity' => __('The default supplier is not configured.')]); }
        $lineTotal = (string) \Brick\Math\BigDecimal::of((string) $row['purchase_price'])->multipliedBy((string) $row['quantity'])->toScale(2);
        if (\Brick\Math\BigDecimal::of($lineTotal)->isGreaterThan('99999999.99')) {
            throw ValidationException::withMessages(['quantity' => __('The purchase total exceeds the supported limit.')]);
        }
        $purchase = Purchase::create([
            'point_of_sale_id'=>$this->shopId,
            'receipt_status'=>'pending',
            'payment_status'=>\App\Models\Supplier::findOrFail($this->supplierId)->is_internal ? 'not_applicable' : 'unknown',
            'currency_code'=>'XAF',
            'supplier_id' => $this->supplierId,
            'user_id' => $this->userId,
            'sub_total' => $lineTotal,
            'tax' => 0,
            'discount_value' => 0,
            'discount_type' => 'fixed',
            'shipping' => 0,
            'grand_total' => $lineTotal,
            'status' => 1,
            'date' => now('Africa/Douala'),
        ]);

        $receiptItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'purchase_price' => round((float) $row['purchase_price'], 2),
            'price' => round((float) $row['price'], 2),
            'quantity' => (int) $row['quantity'],
        ]);
        app(\App\Services\ReceiptStockService::class)->receive($receiptItem,$this->shopId,$this->userId,[
            'expiry_status'=>$row['expiry_status'] ?? (!empty($row['expire_date']) ? 'dated' : 'unknown'),
            'expires_on'=>$row['expire_date'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.sku' => ['nullable', 'string', 'max:255'],
            '*.description' => ['nullable', 'string', 'max:5000'],
            '*.brand' => ['nullable', 'string', 'max:255'],
            '*.category' => ['nullable', 'string', 'max:255'],
            '*.unit' => ['nullable', 'string', 'max:255'],
            '*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            '*.discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            '*.discount_type' => ['nullable', 'in:fixed,percentage'],
            '*.purchase_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            '*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            '*.expire_date' => ['nullable', 'date'],
            '*.expiry_status' => ['nullable','in:dated,not_applicable,unknown'],
            '*.status' => ['nullable', 'boolean'],
        ];
    }
}

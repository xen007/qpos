<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function __construct(
        private readonly int $supplierId,
        private readonly int $userId,
    ) {
    }

    public function model(array $row)
    {
        $validator = Validator::make($row, [
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

        $brand = Brand::firstOrCreate(['name' => trim($row['brand'])]);
        $category = Category::firstOrCreate(['name' => trim($row['category'])]);
        $unitName = trim($row['unit']);
        $unit = Unit::firstOrCreate(
            ['title' => $unitName],
            ['short_name' => $unitName]
        );

        $originalSku = trim($row['sku']);
        $sku = $originalSku;
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter++;
        }

        $product = Product::create([
            'name' => trim($row['name']),
            'sku' => $sku,
            'description' => $row['description'] ?? null,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'price' => round((float) $row['price'], 2),
            'discount' => round((float) ($row['discount'] ?? 0), 2),
            'discount_type' => $row['discount_type'],
            'purchase_price' => round((float) $row['purchase_price'], 2),
            'quantity' => (int) $row['quantity'],
            'expire_date' => $row['expire_date'] ?? null,
            'status' => (bool) $row['status'],
        ]);

        $lineTotal = round((float) $row['purchase_price'] * (int) $row['quantity'], 2);
        $purchase = Purchase::create([
            'supplier_id' => $this->supplierId,
            'user_id' => $this->userId,
            'sub_total' => $lineTotal,
            'tax' => 0,
            'discount_value' => 0,
            'discount_type' => 'fixed',
            'shipping' => 0,
            'grand_total' => $lineTotal,
            'status' => 1,
            'date' => now(),
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'purchase_price' => round((float) $row['purchase_price'], 2),
            'price' => round((float) $row['price'], 2),
            'quantity' => (int) $row['quantity'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.sku' => ['required', 'string', 'max:255'],
            '*.description' => ['nullable', 'string', 'max:5000'],
            '*.brand' => ['required', 'string', 'max:255'],
            '*.category' => ['required', 'string', 'max:255'],
            '*.unit' => ['required', 'string', 'max:255'],
            '*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            '*.discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            '*.discount_type' => ['required', 'in:fixed,percentage'],
            '*.purchase_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            '*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            '*.expire_date' => ['nullable', 'date'],
            '*.status' => ['required', 'boolean'],
        ];
    }
}

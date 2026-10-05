<?php
namespace App\Support;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

final class ProductMoneyValidation
{
    public static function check(FormRequest $request, Validator $validator): array
    {
        $amounts = [];
        foreach (['price', 'purchase_price', 'discount'] as $field) {
            if ($validator->errors()->has($field) || !$request->filled($field)) { continue; }
            try {
                $amounts[$field] = MoneyDecimal::parse($request->input($field), $field);
                if (!PricingSchema::ready() && ($amounts[$field]->isGreaterThan('99999999.99')
                    || !$amounts[$field]->isEqualTo($amounts[$field]->toScale(2, \Brick\Math\RoundingMode::HalfUp)))) {
                    $validator->errors()->add($field, __('Pricing is unavailable until its migrations are applied.'));
                }
            }
            catch (ValidationException $exception) {
                foreach ($exception->errors() as $key => $messages) { foreach ($messages as $message) { $validator->errors()->add($key, $message); } }
            }
        }
        if ($request->filled('sku') && !$validator->errors()->has('sku') && CatalogueSchema::ready()) {
            $barcode = \App\Models\ProductBarcode::with('productUnit')->where('barcode', $request->input('sku'))->first();
            $product = $request->route('product');
            $id = $product instanceof \App\Models\Product ? $product->id : $product;
            if ($barcode && (!$barcode->productUnit->is_reference || (int) $barcode->productUnit->product_id !== (int) $id)) {
                $validator->errors()->add('sku', __('This barcode is already in use.'));
            }
        }
        $discount = $amounts['discount'] ?? BigDecimal::zero();
        if ($request->input('discount_type') === 'percentage' && $discount->isGreaterThan('100')) {
            $validator->errors()->add('discount', __('A percentage discount cannot exceed 100.'));
        } elseif ($request->input('discount_type') === 'fixed' && isset($amounts['price']) && $discount->isGreaterThan($amounts['price'])) {
            $validator->errors()->add('discount', __('A fixed discount cannot exceed the product price.'));
        }
        return $amounts;
    }
}

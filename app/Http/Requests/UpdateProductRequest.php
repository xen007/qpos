<?php
namespace App\Http\Requests;
use App\Models\Product;
use App\Support\ProductMoneyValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Brick\Math\BigDecimal;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');
        if (!$product instanceof Product) { $product = Product::find($product); }
        return $product === null || (bool) $this->user()?->can('update', $product);
    }
    public function rules(): array
    {
        $product = $this->route('product');
        $id = $product instanceof Product ? $product->id : $product;
        return [
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($id)],
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'allows_fractional' => [Rule::excludeIf(!\App\Support\CatalogueSchema::ready() || !$this->user()?->hasRole('Admin')), 'nullable', 'boolean'],
            'price' => 'required|numeric|min:0|max:99999999999999.999999',
            'discount' => 'nullable|numeric|min:0|max:99999999999999.999999|required_with:discount_type',
            'discount_type' => ['nullable', 'required_with:discount', Rule::in(['fixed', 'percentage'])],
            'purchase_price' => 'nullable|numeric|min:0|max:99999999999999.999999',
            'quantity' => 'prohibited',
            'expire_date' => 'nullable|date',
            'status' => 'nullable|boolean',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $amounts = ProductMoneyValidation::check($this, $validator);
            $product = $this->route('product');
            if (!$product instanceof Product) { $product = Product::find($product); }
            if ($product && isset($amounts['purchase_price']) && !$this->user()?->hasRole('Admin')
                && !BigDecimal::of((string) ($product->catalogue_reference_cost ?? $product->purchase_price))->isEqualTo($amounts['purchase_price'])) {
                $validator->errors()->add('purchase_price', __('Only administrators may change the reference purchase cost.'));
            }
        });
    }
}

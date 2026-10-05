<?php
namespace App\Http\Requests;
use App\Models\Product;
use App\Support\ProductMoneyValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->can('create', Product::class); }
    public function rules(): array
    {
        return [
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:products,slug',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'allows_fractional' => [Rule::excludeIf(!\App\Support\CatalogueSchema::ready()), 'nullable', 'boolean'],
            'price' => 'required|numeric|min:0|max:99999999999999.999999',
            'discount' => 'nullable|numeric|min:0|max:99999999999999.999999|required_with:discount_type',
            'discount_type' => ['nullable', 'required_with:discount', Rule::in(['fixed', 'percentage'])],
            'purchase_price' => 'nullable|numeric|min:0|max:99999999999999.999999',
            'quantity' => 'nullable|integer|min:0|max:2147483647',
            'expire_date' => 'nullable|date',
            'status' => 'nullable|boolean',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => ProductMoneyValidation::check($this, $validator));
    }
}

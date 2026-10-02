<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Meme droit que le middleware de route (permission:product_create).
        return (bool) $this->user()?->can('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'price' => 'required|numeric|min:0|max:99999999.99',
            'discount' => 'nullable|numeric|min:0|max:99999999.99|required_with:discount_type',
            'discount_type' => ['nullable', 'required_with:discount', Rule::in(['fixed', 'percentage'])],
            'purchase_price' => 'nullable|numeric|min:0|max:99999999.99',
            'quantity' => 'nullable|integer|min:0|max:2147483647',
            'expire_date' => 'nullable|date',
            'status' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $discount = (float) $this->input('discount', 0);
            $price = (float) $this->input('price', 0);

            if ($this->input('discount_type') === 'percentage' && $discount > 100) {
                $validator->errors()->add('discount', __('A percentage discount cannot exceed 100.'));
            } elseif ($this->input('discount_type') === 'fixed' && $discount > $price) {
                $validator->errors()->add('discount', __('A fixed discount cannot exceed the product price.'));
            }
        });
    }
}

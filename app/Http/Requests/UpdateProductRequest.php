<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\Product;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        if (! $product instanceof Product) {
            $product = Product::find($product);
        }

        // Meme droit que le middleware de route (permission:product_update).
        // Si le produit n'existe pas, le controleur renvoie 404 : on ne modifie
        // pas ce comportement en refusant ici.
        return $product === null || (bool) $this->user()?->can('update', $product);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product'); // Get the product ID from the route

        return [
          'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
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
            $routeProduct = $this->route('product');
            $currentProduct = $routeProduct instanceof Product ? $routeProduct : Product::find($routeProduct);
            if ($currentProduct && !$validator->errors()->has('purchase_price') && $this->filled('purchase_price') && is_numeric($this->input('purchase_price'))
                && !$this->user()?->hasRole('Admin')
                && !\Brick\Math\BigDecimal::of((string) $currentProduct->purchase_price)->isEqualTo((string) $this->input('purchase_price'))) {
                $validator->errors()->add('purchase_price', __('Only administrators may change the reference purchase cost.'));
            }
            $discount = (float) $this->input('discount', 0);
            $price = $this->input('price');
            if ($price === null) {
                $routeProduct = $this->route('product');
                $productId = $routeProduct instanceof Product ? $routeProduct->getKey() : $routeProduct;
                $price = Product::whereKey($productId)->value('price') ?? 0;
            }

            if ($this->input('discount_type') === 'percentage' && $discount > 100) {
                $validator->errors()->add('discount', __('A percentage discount cannot exceed 100.'));
            } elseif ($this->input('discount_type') === 'fixed' && $discount > (float) $price) {
                $validator->errors()->add('discount', __('A fixed discount cannot exceed the product price.'));
            }
        });
    }
}

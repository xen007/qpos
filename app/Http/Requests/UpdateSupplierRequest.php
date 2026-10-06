<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supplierId = $this->route('supplier') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('suppliers', 'phone')->ignore($supplierId),
            ],
            'address' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

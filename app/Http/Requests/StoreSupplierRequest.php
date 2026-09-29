<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
     * Comme pour les clients, une requete JSON ne transmet que le nom : le jeu
     * de regles reduit d'origine est conserve pour ce cas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->wantsJson()) {
            return [
                'name' => 'required|string',
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'address' => 'nullable|string|max:255',
        ];
    }
}

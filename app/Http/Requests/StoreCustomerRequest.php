<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Meme droit que le middleware de route (permission:customer_create),
        // y compris pour la creation JSON depuis le POS.
        return (bool) $this->user()?->can('create', Customer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Deux points d'entree partagent cette action :
     *   - le formulaire du back-office (POST /admin/customers) ;
     *   - le POS, en JSON, qui ne transmet que le nom
     *     (POST /admin/create/customers) : le jeu de regles reduit d'origine
     *     est donc conserve pour les requetes JSON.
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
            'phone' => 'required|string|max:20|unique:customers,phone',
            'address' => 'nullable|string|max:255',
        ];
    }
}

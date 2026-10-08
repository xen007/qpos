<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        if (! $customer instanceof Customer) {
            $customer = Customer::find($customer);
        }

        // Meme droit que le middleware de route (permission:customer_update).
        // Si le client n'existe pas, le controleur renvoie 404 : on ne modifie
        // pas ce comportement en refusant ici.
        return $customer === null || (bool) $this->user()?->can('update', $customer);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'address' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

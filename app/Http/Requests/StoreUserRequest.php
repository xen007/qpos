<?php

namespace App\Http\Requests;

use App\Rules\ValidImageType;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool)$this->user()?->can('user_create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * La route users/create repond en GET (affichage du formulaire) et en POST
     * (creation) : aucune regle n'est appliquee sur GET, comme dans le
     * controleur d'origine.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'roles'=>'required|array|min:1|max:10',
            'roles.*'=>'required|integer|distinct|exists:roles,id',
            'password' => 'required|string|min:8|max:255',
            'profile_image' => ['file', new ValidImageType, 'max:2048'],
        ];
    }
    protected function prepareForValidation(): void
    {
        if(!$this->has('roles') && $this->filled('role')) $this->merge(['roles'=>[$this->input('role')]]);
    }
}

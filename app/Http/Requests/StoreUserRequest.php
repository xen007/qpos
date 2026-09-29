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
        return true;
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
            'role' => 'required|exists:roles,id',
            'password' => 'required|string|min:8|max:255',
            'profile_image' => ['file', new ValidImageType, 'max:2048'],
        ];
    }
}

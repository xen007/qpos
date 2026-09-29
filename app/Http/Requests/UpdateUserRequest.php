<?php

namespace App\Http\Requests;

use App\Rules\ValidImageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
     * La route users/edit/{id} repond en GET (affichage du formulaire) et en
     * POST (mise a jour) : aucune regle n'est appliquee sur GET.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        $userId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|max:255',
            'profile_image' => ['file', new ValidImageType, 'max:2048'],
        ];
    }
}

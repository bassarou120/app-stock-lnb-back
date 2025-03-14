<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class RegisterRequest extends FormRequest
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
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'sexe' => ['required', Rule::in(['Masculin', 'Féminin'])],
            'active' => 'required|boolean'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'Email obligatoire',
            'email.email' => 'Format email invalide',
            'email.unique' => 'Email déjà utilisé',
            'sexe.required' => 'Le sexe est obligatoire',
            'sexe.in' => 'Le sexe est entre Féminin ou Masculin',
            'phone.unique' => 'Le téléphone est déjà utilisé',
            'phone.required' => 'Le téléphone est obligatoire',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException(
            $validator,
            response()->json([
                'errors' => $validator->errors()
            ], 422)
        );
    }
}

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
            'employe_id' => 'required|exists:employes,id|unique:users,employe_id',
            // 'name' => 'required',
            // 'email' => 'required|email|unique:users,email',
            // 'phone' => 'required|unique:users,phone',
            'sexe' => ['nullable', Rule::in(['Masculin', 'Féminin'])],
            'active' => 'required|boolean',
            'role_id' => 'required|exists:roles,id'
        ];
    }

    public function messages()
    {
        return [
            'employe_id.required' => 'L\'employé est obligatoire.',
            'employe_id.exists' => 'L\'employé sélectionné n\'existe pas.',
            'employe_id.unique' => 'Un utilisateur existe déjà pour cet employé.',
            // 'name.required' => 'Le nom est obligatoire',
            // 'email.required' => 'Email obligatoire',
            // 'email.email' => 'Format email invalide',
            // 'email.unique' => 'Email déjà utilisé',
            // 'sexe.nullable' => 'Le sexe est obligatoire',
            'sexe.in' => 'Le sexe est entre Féminin ou Masculin',
            // 'phone.unique' => 'Le téléphone est déjà utilisé',
            // 'phone.required' => 'Le téléphone est obligatoire',
            'active.required' => 'Le statut actif est obligatoire.',
            'active.boolean' => 'Le statut actif doit être un booléen.',
            'role_id.required' => 'Le rôle est obligatoire.',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas.',
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

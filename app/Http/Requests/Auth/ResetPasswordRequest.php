<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', 'min:8', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => "Link di reset non valido",
            'email.required'     => "L'email è obbligatoria",
            'email.email'        => "Inserisci un indirizzo email valido",
            'password.required'  => "La password è obbligatoria",
            'password.confirmed' => "Le due password non coincidono",
            'password.min'       => "La password è troppo corta, deve essere almeno di 8 caratteri",
        ];
    }
}

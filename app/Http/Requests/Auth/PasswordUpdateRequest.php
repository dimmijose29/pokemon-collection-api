<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * La rotta agisce sempre sull'utente autenticato, quindi basta essere loggati.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password'         => ['required', 'string', 'confirmed', 'min:8', 'different:current_password', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => "Inserisci la password attuale",
            'current_password.current_password' => "La password attuale non è corretta",
            'password.required'                 => "La password è obbligatoria",
            'password.confirmed'                => "Le due password non coincidono",
            'password.min'                      => "La password è troppo corta, deve essere almeno di 8 caratteri",
            'password.different'                => "La nuova password deve essere diversa da quella attuale",
        ];
    }
}

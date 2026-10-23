<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorDisableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Disattivare la 2FA richiede sia la password sia un codice valido:
     * un token rubato da solo non basta per abbassare la protezione dell'account.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:sanctum'],
            'code'     => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'         => "Inserisci la password attuale",
            'password.current_password' => "La password non è corretta",
            'code.required'             => "Inserisci il codice 2FA",
            'code.size'                 => "Il codice 2FA deve essere di 6 cifre",
        ];
    }
}

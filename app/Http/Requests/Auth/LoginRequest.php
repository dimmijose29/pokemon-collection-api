<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (! \DB::table('users')
                        ->where('email', $value)
                        ->orWhere('username', $value)
                        ->exists()) {
                        $fail('Nessun utente trovato con queste credenziali');
                    }
                },
            ],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => "Inserisci email o username",
            'password.required' => "La password è obbligatoria",
        ];
    }

    /**
     * Restituisce le credenziali per Auth::attempt()
     */
    public function getCredentials(): array
    {
        $login = trim($this->input('login'));

        return filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $login, 'password' => $this->input('password')]
            : ['username' => $login, 'password' => $this->input('password')];
    }
}
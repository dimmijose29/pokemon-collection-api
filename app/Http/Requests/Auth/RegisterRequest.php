<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // chiunque può registrarsi
    }

    public function rules(): array
    {
        return [
            'email'              => ['required', 'email', 'max:255', 'unique:users,email'],
            'username'           => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'password'           => ['required', 'string', 'confirmed', 'min:8', Password::defaults()],
            'country'            => ['nullable', 'string', 'max:100'],
            'preferred_currency' => ['nullable', 'string', 'size:3'], // es. EUR, USD – possiamo validare con exists:currencies,code dopo
            'birth_year'         => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year - 13)],
            'avatar_url'         => ['nullable', 'url', 'max:255'],
            'bio'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'              => "L'email è obbligatoria",
            'email.email'                 => "Inserisci un indirizzo email valido",
            'email.unique'                => "Questa email è già registrata",
            'username.unique'             => "Questo username è già in uso",
            'password.required'           => "La password è obbligatoria",
            'password.min'                => "La password deve avere almeno 8 caratteri",
            'password.confirmed'           => "Le due password non coincidono",
            'birth_year.max'              => "Devi avere almeno 13 anni per registrarti",
            'avatar_url.url'              => "L'URL dell'avatar non è valido",
            'bio.max'                     => "La bio non può superare i 1000 caratteri",
            // puoi aggiungere altri messaggi personalizzati
        ];
    }

    protected function prepareForValidation()
    {
        // Opzionale: normalizza alcuni campi
        $this->merge([
            'email' => strtolower(trim($this->email)),
        ]);
    }
}
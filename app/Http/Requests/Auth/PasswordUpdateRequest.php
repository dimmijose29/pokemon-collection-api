<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
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
            'password' => ['required','confirmed','min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'            => "La password è obbligatoria",
            'password.confirmed'           => "Le due password non coincidono",
            'password.min'                 => "La password è troppo corta, deve essere almeno di 8 caratteri",
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Language;

class LanguageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) return false; // non autenticato

        // Per GET (index/show) → chiama policy viewAny/view
        if ($this->method() === 'GET') {
            if ($this->route('language')) return $user->can('view', $this->route('language'));
            return $user->can('viewAny', Language::class);
        }

        // Per POST (create) → chiama policy create
        if ($this->method() === 'POST') {
            return $user->can('create', Language::class);
        }

        // Per PUT/PATCH (update) → chiama policy update
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            if (!$this->route('language')) return false;
            return $user->can('update', $this->route('language'));
        }

        // Per DELETE → chiama policy delete
        if ($this->method() === 'DELETE') {
            if (!$this->route('language')) return false;
            return $user->can('delete', $this->route('language'));
        }

        // Default
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:10',
            ],
            'name' => [
                'required',
                'string',
                'max:50',
            ],
            'country_code' => [
                'required',
                'string',
                'max:10',
            ],
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $rules['code'][] = Rule::unique('languages', 'code')->ignore($this->route('language'));
            $rules['name'][] = Rule::unique('languages', 'name')->ignore($this->route('language'));
            $rules['country_code'][] = Rule::unique('languages', 'country_code')->ignore($this->route('language'));
        } else {
            // CREATE: unique semplice
            $rules['code'][] = Rule::unique('languages', 'code');
            $rules['name'][] = Rule::unique('languages', 'name');
            $rules['country_code'][] = Rule::unique('languages', 'country_code');
        }

        return $rules;
    }

    /**
     * Messaggi di errore personalizzati in italiano
     */
    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio.",
            'code.unique'   => "Questo codice è già in uso.",
            'code.max'      => "Il codice non può superare i 10 caratteri.",
            'name.required' => "Il nome della lingua è obbligatorio.",
            'name.max'      => "Il nome non può superare i 50 caratteri.",
            'name.unique'   => "Questo nome è già in uso.",
            'country_code.required' => "Il codice paese è obbligatorio.",
            'country_code.max'      => "Il codice paese non può superare i 10 caratteri.",
            'country_code.unique'   => "Questo codice paese è già in uso.",
        ];
    }

    /**
     * Preparazione dati prima della validazione
     * (opzionale, utile per normalizzare)
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'country_code' => strtoupper(trim($this->input('country_code', ''))),
        ]);
    }
}

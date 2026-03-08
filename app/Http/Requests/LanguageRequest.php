<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // per ora; poi useremo policy o gate
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
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $rules['code'][] = Rule::unique('languages', 'code')->ignore($this->route('language'));
        }

        // Se è un UPDATE (PUT/PATCH), aggiungiamo la regola unique ignorando l'ID corrente
        if ($this->isMethod('put') || $this->isMethod('patch') || $this->filled('language_id')) {
            $languageId = $this->route('language')?->id ?? $this->input('language_id');

            $rules['code'][] = Rule::unique('languages', 'code')->ignore($languageId);
            $rules['name'][] = Rule::unique('languages', 'name')->ignore($languageId);
        } else {
            // CREATE: unique semplice
            $rules['code'][] = Rule::unique('languages', 'code');
            $rules['name'][] = Rule::unique('languages', 'name');
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
        ];
    }

    /**
     * Preparazione dati prima della validazione
     * (opzionale, utile per normalizzare)
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper(trim($this->input('code', ''))),
        ]);
    }
}

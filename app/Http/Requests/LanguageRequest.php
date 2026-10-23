<?php

namespace App\Http\Requests;

class LanguageRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', $this->unique('languages', 'code')],
            'name' => ['required', 'string', 'max:50', $this->unique('languages', 'name')],
            'country_code' => ['required', 'string', 'max:10', $this->unique('languages', 'country_code')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio",
            'code.unique'   => "Questo codice è già in uso",
            'code.max'      => "Il codice non può superare i 10 caratteri",
            'name.required' => "Il nome della lingua è obbligatorio",
            'name.max'      => "Il nome non può superare i 50 caratteri",
            'name.unique'   => "Questo nome è già in uso",
            'country_code.required' => "Il codice paese è obbligatorio",
            'country_code.max'      => "Il codice paese non può superare i 10 caratteri",
            'country_code.unique'   => "Questo codice paese è già in uso",
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

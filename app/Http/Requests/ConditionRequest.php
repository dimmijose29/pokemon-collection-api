<?php

namespace App\Http\Requests;

class ConditionRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', $this->unique('conditions', 'code')],
            'name' => ['required', 'string', 'max:50', $this->unique('conditions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio",
            'code.unique'   => "Questo codice è già in uso",
            'code.max'      => "Il codice non può superare i 10 caratteri",
            'name.required' => "Il nome della condizione è obbligatorio",
            'name.max'      => "Il nome non può superare i 50 caratteri",
            'name.unique'   => "Questo nome è già in uso",
        ];
    }
}

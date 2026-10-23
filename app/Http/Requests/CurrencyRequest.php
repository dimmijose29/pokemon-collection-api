<?php

namespace App\Http\Requests;

class CurrencyRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', $this->unique('currencies', 'code')],
            'symbol' => ['required', 'string', 'max:10', $this->unique('currencies', 'symbol')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio",
            'code.unique'   => "Questo codice è già in uso",
            'code.max'      => "Il codice non può superare i 10 caratteri",
            'symbol.required' => "Il simbolo della valuta è obbligatorio",
            'symbol.max'      => "Il simbolo non può superare i 10 caratteri",
            'symbol.unique'   => "Questo simbolo è già in uso",
        ];
    }
}

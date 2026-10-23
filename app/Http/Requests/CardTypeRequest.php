<?php

namespace App\Http\Requests;

class CardTypeRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->unique('card_types', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Il nome del tipo di carta è obbligatorio",
            'name.unique'   => "Questo tipo di carta esiste già",
            'name.max'      => "Il nome non può superare i 50 caratteri",
        ];
    }
}

<?php

namespace App\Http\Requests;

class RarityRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->unique('rarities', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Il nome della rarità è obbligatorio",
            'name.unique'   => "Questa rarità esiste già",
            'name.max'      => "Il nome non può superare i 50 caratteri",
        ];
    }
}

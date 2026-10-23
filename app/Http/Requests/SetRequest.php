<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SetRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            // Obbligatorio in creazione: la colonna è NOT NULL e identifica il record per la sync
            'tcgdex_id'           => [
                $this->isCreate() ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('sets', 'tcgdex_id')->ignore($this->recordId()),
            ],
            'name'                => ['required', 'string', 'max:100'],
            'series_id'           => ['nullable', 'integer', 'exists:series,id'],
            'card_count_official' => ['nullable', 'integer', 'min:0'],
            'card_count_total'    => ['nullable', 'integer', 'min:0'],
            'release_date'        => ['nullable', 'date'],
            'symbol_url'          => ['nullable', 'string', 'max:500'],
            'logo_url'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Il nome del set è obbligatorio',
            'name.max'        => 'Il nome non può superare i 100 caratteri',
            'tcgdex_id.required' => "L'ID TCGdex è obbligatorio",
            'tcgdex_id.unique' => 'Questo ID TCGdex è già in uso',
            'release_date.date' => 'La data di uscita non è valida',
        ];
    }
}

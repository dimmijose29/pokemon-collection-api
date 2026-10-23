<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CardRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            // Obbligatorio in creazione: la colonna è NOT NULL e identifica il record per la sync
            'tcgdex_id'    => [
                $this->isCreate() ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::unique('cards', 'tcgdex_id')->ignore($this->recordId()),
            ],
            'set_id'       => ['required', 'exists:sets,id'],
            'artist_id'    => ['nullable', 'exists:artists,id'],
            'rarity_id'    => ['nullable', 'exists:rarities,id'],
            'card_type_id' => ['nullable', 'exists:card_types,id'],
            'local_id'     => ['required', 'string', 'max:20'],
            'image_url'    => ['nullable', 'string', 'max:500'],
            'stage'        => ['nullable', 'string', 'max:50'],
            'energy_types' => ['nullable', 'array'],
            'energy_types.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'set_id.required'  => 'Il set è obbligatorio',
            'set_id.exists'    => 'Il set selezionato non esiste',
            'local_id.required' => 'Il numero carta è obbligatorio',
            'tcgdex_id.required' => "L'ID TCGdex è obbligatorio",
            'tcgdex_id.unique' => 'Questo ID TCGdex è già in uso',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->method() === 'GET') {
            return true;
        }

        if ($this->method() === 'POST') {
            return $user->can('create', \App\Models\Card::class);
        }

        $card = $this->route('card');

        if (! $card) {
            return false;
        }

        $ability = $this->method() === 'DELETE' ? 'delete' : 'update';

        return $user->can($ability, $card);
    }

    public function rules(): array
    {
        $cardId = $this->route('card')?->id;

        return [
            // Obbligatorio in creazione: la colonna è NOT NULL e identifica il record per la sync
            'tcgdex_id'    => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::unique('cards', 'tcgdex_id')->ignore($cardId),
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

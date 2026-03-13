<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // GET pubblici
        if ($this->method() === 'GET') {
            return true;
        }

        // POST → create (solo admin)
        if ($this->method() === 'POST') {
            return $user->can('create', \App\Models\CardType::class);
        }

        // PUT/PATCH/DELETE → update/delete (solo admin)
        $cardType = $this->route('cardType');

        if (! $cardType) {
            return false;
        }

        $ability = $this->method() === 'DELETE' ? 'delete' : 'update';

        return $user->can($ability, $cardType);
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:50',
            ],
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $rules['name'][] = Rule::unique('card_types', 'name')->ignore($this->route('cardType'));
        } else {
            $rules['name'][] = Rule::unique('card_types', 'name');
        }

        return $rules;
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
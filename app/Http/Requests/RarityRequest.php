<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RarityRequest extends FormRequest
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
            return $user->can('create', \App\Models\Rarity::class);
        }

        // PUT/PATCH/DELETE → update/delete (solo admin)
        $rarity = $this->route('rarity');

        if (! $rarity) {
            return false;
        }

        $ability = $this->method() === 'DELETE' ? 'delete' : 'update';

        return $user->can($ability, $rarity);
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
            $rules['name'][] = Rule::unique('rarities', 'name')->ignore($this->route('rarity'));
        } else {
            $rules['name'][] = Rule::unique('rarities', 'name');
        }

        return $rules;
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
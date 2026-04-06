<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArtistRequest extends FormRequest
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
            return $user->can('create', \App\Models\Artist::class);
        }

        // PUT/PATCH/DELETE → update/delete (solo admin)
        $artist = $this->route('artist');

        if (! $artist) {
            return false;
        }

        $ability = $this->method() === 'DELETE' ? 'delete' : 'update';

        return $user->can($ability, $artist);
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
            $rules['name'][] = Rule::unique('artists', 'name')->ignore($this->route('artist'));
        } else {
            $rules['name'][] = Rule::unique('artists', 'name');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => "Il nome dell'artista è obbligatorio",
            'name.unique'   => "Questo artista esiste già",
            'name.max'      => "Il nome non può superare i 50 caratteri",
        ];
    }
}
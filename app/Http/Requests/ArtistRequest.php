<?php

namespace App\Http\Requests;

class ArtistRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->unique('artists', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Il nome dell'artista è obbligatorio",
            'name.unique'   => "Questo artista è stato già inserito",
            'name.max'      => "Il nome non può superare i 50 caratteri",
        ];
    }
}

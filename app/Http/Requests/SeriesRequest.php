<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SeriesRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100',
                            Rule::unique('series', 'name')->ignore($this->recordId())->whereNull('deleted_at')],
            'tcgdex_id' => ['nullable', 'string', 'max:50',
                            Rule::unique('series', 'tcgdex_id')->ignore($this->recordId())->whereNull('deleted_at')],
            'logo_url'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Il nome della serie è obbligatorio',
            'name.unique'   => 'Questa serie esiste già',
            'name.max'      => 'Il nome non può superare i 100 caratteri',
        ];
    }
}

<?php

namespace App\Http\Requests;

class GradingCompanyRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->unique('grading_companies', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Il nome della compagnia di valutazione è obbligatorio",
            'name.unique'   => "Questa compagnia di valutazione esiste già",
            'name.max'      => "Il nome non può superare i 50 caratteri",
        ];
    }
}

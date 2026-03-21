<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradingCompanyRequest extends FormRequest
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
            return $user->can('create', \App\Models\GradingCompany::class);
        }

        // PUT/PATCH/DELETE → update/delete (solo admin)
        $gradingCompany = $this->route('gradingCompany');

        if (! $gradingCompany) {
            return false;
        }

        $ability = $this->method() === 'DELETE' ? 'delete' : 'update';

        return $user->can($ability, $gradingCompany);
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
            $rules['name'][] = Rule::unique('grading_companies', 'name')->ignore($this->route('gradingCompany'));
        } else {
            $rules['name'][] = Rule::unique('grading_companies', 'name');
        }

        return $rules;
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
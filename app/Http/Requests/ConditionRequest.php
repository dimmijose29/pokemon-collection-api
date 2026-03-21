<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Condition;

class ConditionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) return false; // non autenticato

        // Per GET (index/show) → chiama policy viewAny/view
        if ($this->method() === 'GET') {
            if ($this->route('condition')) return $user->can('view', $this->route('condition'));
            return $user->can('viewAny', Condition::class);
        }

        // Per POST (create) → chiama policy create
        if ($this->method() === 'POST') {
            return $user->can('create', Condition::class);
        }

        // Per PUT/PATCH (update) → chiama policy update
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            if (!$this->route('condition')) return false;
            return $user->can('update', $this->route('condition'));
        }

        // Per DELETE → chiama policy delete
        if ($this->method() === 'DELETE') {
            if (!$this->route('condition')) return false;
            return $user->can('delete', $this->route('condition'));
        }

        // Default
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'code' => [
                'required',
                'string',
                'max:10',
            ],
            'name' => [
                'required',
                'string',
                'max:50',
            ],
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $rules['code'][] = Rule::unique('conditions', 'code')->ignore($this->route('condition'));
            $rules['name'][] = Rule::unique('conditions', 'name')->ignore($this->route('condition'));
        } else {
            // CREATE: unique semplice
            $rules['code'][] = Rule::unique('conditions', 'code');
            $rules['name'][] = Rule::unique('conditions', 'name');
        }

        return $rules;
    }

    /**
     * Messaggi di errore personalizzati in italiano
     */
    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio.",
            'code.unique'   => "Questo codice è già in uso.",
            'code.max'      => "Il codice non può superare i 10 caratteri.",
            'name.required' => "Il nome della condizione è obbligatorio.",
            'name.max'      => "Il nome non può superare i 50 caratteri.",
            'name.unique'   => "Questo nome è già in uso.",
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Currency;

class CurrencyRequest extends FormRequest
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
            if ($this->route('currency')) return $user->can('view', $this->route('currency'));
            return $user->can('viewAny', Currency::class);
        }

        // Per POST (create) → chiama policy create
        if ($this->method() === 'POST') {
            return $user->can('create', Currency::class);
        }

        // Per PUT/PATCH (update) → chiama policy update
        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            if (!$this->route('currency')) return false;
            return $user->can('update', $this->route('currency'));
        }

        // Per DELETE → chiama policy delete
        if ($this->method() === 'DELETE') {
            if (!$this->route('currency')) return false;
            return $user->can('delete', $this->route('currency'));
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
            'symbol' => [
                'required',
                'string',
                'max:10',
            ],
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $rules['code'][] = Rule::unique('currencies', 'code')->ignore($this->route('currency'));
            $rules['symbol'][] = Rule::unique('currencies', 'symbol')->ignore($this->route('currency'));
        } else {
            // CREATE: unique semplice
            $rules['code'][] = Rule::unique('currencies', 'code');
            $rules['symbol'][] = Rule::unique('currencies', 'symbol');
        }

        return $rules;
    }

    /**
     * Messaggi di errore personalizzati in italiano
     */
    public function messages(): array
    {
        return [
            'code.required' => "Il codice è obbligatorio",
            'code.unique'   => "Questo codice è già in uso",
            'code.max'      => "Il codice non può superare i 10 caratteri",
            'symbol.required' => "Il simbolo della valuta è obbligatorio",
            'symbol.max'      => "Il simbolo non può superare i 10 caratteri",
            'symbol.unique'   => "Questo simbolo è già in uso",
        ];
    }
}

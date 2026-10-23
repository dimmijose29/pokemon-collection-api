<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;
        if ($this->method() === 'GET') return true;
        return $user->is_admin;
    }

    public function rules(): array
    {
        $seriesId = $this->route('series')?->id;

        return [
            'name'      => ['required', 'string', 'max:100',
                            Rule::unique('series', 'name')->ignore($seriesId)->whereNull('deleted_at')],
            'tcgdex_id' => ['nullable', 'string', 'max:50',
                            Rule::unique('series', 'tcgdex_id')->ignore($seriesId)->whereNull('deleted_at')],
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

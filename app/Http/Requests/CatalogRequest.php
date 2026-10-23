<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Base delle FormRequest di catalogo.
 *
 * L'autorizzazione è sulle rotte (`auth:sanctum` + `admin` sulle scritture),
 * quindi qui non si ripete: ogni request concreta definisce solo regole e messaggi.
 */
abstract class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Id del record in modifica (null in creazione). */
    protected function recordId(): ?string
    {
        return $this->route('id');
    }

    /** Vincolo unique che in modifica ignora il record corrente. */
    protected function unique(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->ignore($this->recordId());
    }

    protected function isCreate(): bool
    {
        return $this->isMethod('POST');
    }
}

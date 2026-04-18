<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'code'         => $this->code,
            'name'         => $this->name,
            'country_code' => $this->country_code,
            'created_at'   => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'deleted_at'   => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

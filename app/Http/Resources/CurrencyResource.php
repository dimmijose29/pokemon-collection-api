<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'symbol'     => $this->symbol,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

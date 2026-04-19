<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeriesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'tcgdex_id'  => $this->tcgdex_id,
            'name'       => $this->name,
            'logo_url'   => $this->logo_url,
            'sets_count' => $this->whenCounted('sets'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

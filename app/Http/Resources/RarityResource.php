<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RarityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'deleted_at' => $this->whenNotNull($this->deleted_at), function() {
                return $this->deleted_at->toDateTimeString();
            }
        ];
    }
}
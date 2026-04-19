<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetTranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'language_id' => $this->language_id,
            'language'    => $this->whenLoaded('language', fn() => [
                'id'   => $this->language->id,
                'code' => $this->language->code,
                'name' => $this->language->name,
            ]),
            'name'        => $this->name,
        ];
    }
}

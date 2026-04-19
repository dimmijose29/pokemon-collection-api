<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tcgdex_id'    => $this->tcgdex_id,
            'local_id'     => $this->local_id,
            // TCGdex restituisce URL base — /high.webp è la qualità standard
            'image_url'    => $this->image_url ? $this->image_url . '/high.webp' : null,
            'stage'        => $this->stage,
            'energy_types' => $this->energy_types,
            // Relazioni — incluse solo se caricate
            'set'          => $this->whenLoaded('set', fn() => [
                'id'         => $this->set->id,
                'tcgdex_id'  => $this->set->tcgdex_id,
                'name'       => $this->set->name,
                'logo_url'   => $this->set->logo_url   ? $this->set->logo_url   . '.png' : null,
                'symbol_url' => $this->set->symbol_url ? $this->set->symbol_url . '.png' : null,
            ]),
            'artist'       => $this->whenLoaded('artist', fn() => [
                'id'   => $this->artist?->id,
                'name' => $this->artist?->name,
            ]),
            'rarity'       => $this->whenLoaded('rarity', fn() => [
                'id'   => $this->rarity?->id,
                'name' => $this->rarity?->name,
            ]),
            'card_type'    => $this->whenLoaded('cardType', fn() => [
                'id'   => $this->cardType?->id,
                'name' => $this->cardType?->name,
            ]),
            'translations' => CardTranslationResource::collection($this->whenLoaded('translations')),
            'price'        => $this->whenLoaded('latestCardmarketPrice', fn() => $this->latestCardmarketPrice?->price !== null ? (float) $this->latestCardmarketPrice->price : null),
            'created_at'   => $this->created_at?->toDateTimeString(),
            'deleted_at'   => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tcgdex_id'           => $this->tcgdex_id,
            'name'                => $this->name,
            'series_id'           => $this->series_id,
            'series'              => $this->whenLoaded('series', fn() => new SeriesResource($this->series)),
            'card_count_official' => $this->card_count_official,
            'card_count_total'    => $this->card_count_total,
            'release_date'        => $this->release_date?->toDateString(),
            // TCGdex restituisce URL base — aggiungiamo l'estensione per il frontend
            'symbol_url'          => $this->symbol_url ? $this->symbol_url . '.png' : null,
            'logo_url'            => $this->logo_url   ? $this->logo_url   . '.png' : null,
            // Incluso solo quando esplicitamente caricato (es. nella show)
            'translations'        => SetTranslationResource::collection($this->whenLoaded('translations')),
            'created_at'          => $this->created_at?->toDateTimeString(),
            'deleted_at'          => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

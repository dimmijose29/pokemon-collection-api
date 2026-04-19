<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'email'              => $this->email,
            'username'           => $this->username,
            'is_admin'           => $this->is_admin,
            'is_active'          => $this->is_active,
            'is_2fa_enabled'     => $this->is_2fa_enabled,
            'country'            => $this->whenNotNull($this->country),
            'preferred_currency' => $this->whenNotNull($this->preferred_currency),
            'birth_year'         => $this->whenNotNull($this->birth_year),
            'avatar_url'         => $this->whenNotNull($this->avatar_url),
            'bio'                => $this->whenNotNull($this->bio),
            'created_at'         => $this->created_at?->toDateTimeString(),
            'deleted_at'         => $this->whenNotNull($this->deleted_at?->toDateTimeString()),
        ];
    }
}

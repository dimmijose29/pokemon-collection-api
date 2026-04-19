<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Set extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tcgdex_id',
        'name',
        'series',
        'card_count_official',
        'card_count_total',
        'release_date',
        'symbol_url',
        'logo_url',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(SetTranslation::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}

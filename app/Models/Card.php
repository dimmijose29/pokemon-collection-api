<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tcgdex_id',
        'set_id',
        'artist_id',
        'rarity_id',
        'card_type_id',
        'local_id',
        'image_url',
        'stage',
        'energy_types',
    ];

    protected $casts = [
        'energy_types' => 'array',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function rarity(): BelongsTo
    {
        return $this->belongsTo(Rarity::class);
    }

    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CardTranslation::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CardPrice::class);
    }

    /**
     * Restituisce il prezzo più recente per una data fonte e tipo.
     */
    public function latestPrice(string $source = 'cardmarket', string $priceType = 'trend'): ?CardPrice
    {
        return $this->prices()
            ->where('source', $source)
            ->where('price_type', $priceType)
            ->latest('recorded_at')
            ->first();
    }
}

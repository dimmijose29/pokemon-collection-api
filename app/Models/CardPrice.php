<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'card_id',
        'source',
        'price_type',
        'price',
        'currency',
        'recorded_at',
        'recorded_on',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'recorded_at' => 'datetime',
        'recorded_on' => 'date',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetTranslation extends Model
{
    protected $fillable = [
        'set_id',
        'language_id',
        'name',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}

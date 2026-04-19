<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tcgdex_id',
        'name',
        'logo_url',
    ];

    public function sets(): HasMany
    {
        return $this->hasMany(Set::class);
    }
}

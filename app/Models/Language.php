<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Language extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'country_code',
    ];

    // Relazione futura con set_translations
    public function setTranslations()
    {
        return $this->hasMany(SetTranslation::class);
    }
}

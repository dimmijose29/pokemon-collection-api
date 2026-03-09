<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

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

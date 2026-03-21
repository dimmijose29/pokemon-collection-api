<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
    ];

    // Relazioni future (opzionale per ora)
    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}

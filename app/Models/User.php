<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'totp_secret',
        'totp_last_used_timestamp',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active'                 => 'boolean',
        'is_2fa_enabled'            => 'boolean',
        'totp_secret'               => 'encrypted',
        'totp_last_used_timestamp'  => 'integer',
        'email_verified_at'         => 'datetime',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at'   => 'datetime',
        'birth_year'                => 'integer',
        'password' => 'hashed',
    ];

    // // Futuro: relazione con collections
    // public function collections()
    // {
    //     return $this->hasMany(Collection::class);
    // }
}

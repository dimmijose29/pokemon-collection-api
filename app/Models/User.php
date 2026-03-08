<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'totp_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'is_active'                 => 'boolean',
        'is_2fa_enabled'            => 'boolean',
        'email_verified_at'         => 'datetime',
        'last_login_at'             => 'datetime',
        'two_factor_recovery_codes' => 'array',
        'birth_year'                => 'integer',
        'password' => 'hashed',
    ];

    // Mutatore per hashed_password (usa bcrypt automaticamente)
    protected function hashedPassword(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => bcrypt($value),
        );
    }

    // Metodo helper per verificare 2FA
    public function hasEnabledTwoFactor(): bool
    {
        return $this->is_2fa_enabled && $this->totp_secret !== null;
    }

    // Futuro: relazione con collections
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }
}
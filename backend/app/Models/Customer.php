<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_guest',
        'is_blocked',
        'internal_notes',
        'phone_verified_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_guest' => 'boolean',
            'is_blocked' => 'boolean',
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'email', 'is_blocked', 'internal_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isAccountValid(): bool
    {
        return ! $this->is_blocked && ! $this->is_guest;
    }
}

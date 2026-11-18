<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read Role|null $role
 * @property-read Patient|null $patient
 * @property-read Employee|null $employee
 */
#[Fillable(['role_id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'password', 'is_approved', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo { return $this->belongsTo(Role::class); }
    public function patient(): HasOne { return $this->hasOne(Patient::class); }
    public function employee(): HasOne { return $this->hasOne(Employee::class); }
    public function getNameAttribute(): string { return trim($this->first_name.' '.$this->last_name); }
}

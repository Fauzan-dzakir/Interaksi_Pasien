<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['unit_id', 'name', 'email', 'role', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCssdStaff(): bool
    {
        return $this->role === UserRole::CssdStaff;
    }

    public function isNakes(): bool
    {
        return $this->role === UserRole::Nakes;
    }

    /** @param  UserRole|array<int, UserRole>  $roles */
    public function hasRole(UserRole|array $roles): bool
    {
        return in_array($this->role, is_array($roles) ? $roles : [$roles], true);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

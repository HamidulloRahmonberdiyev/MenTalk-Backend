<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, SoftDeletes;

    // is_admin, password and blocked_at are deliberately not mass assignable.
    protected $fillable = ['google_id', 'email', 'name'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_admin' => false, 'blocked_at' => null];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->blocked_at === null;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(LearnerProfile::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(VocabCard::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, SoftDeletes;

    public const AVATAR_DISK = 'avatars';

    // is_admin, password, blocked_at and avatar_path are deliberately not mass assignable.
    protected $fillable = ['google_id', 'email', 'name', 'google_avatar_url'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_admin' => false, 'blocked_at' => null, 'avatar_path' => null, 'google_avatar_url' => null];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::forceDeleted(fn (self $user) => Storage::disk(self::AVATAR_DISK)->deleteDirectory($user->id));
    }

    /** The uploaded photo, otherwise the Google one. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path !== null
            ? Storage::disk(self::AVATAR_DISK)->url($this->avatar_path)
            : $this->google_avatar_url);
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

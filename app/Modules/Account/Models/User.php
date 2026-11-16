<?php

declare(strict_types=1);

namespace App\Modules\Account\Models;

use App\Modules\Conversation\Models\Conversation;
use App\Modules\Notification\Models\DatabaseNotification;
use App\Modules\Vocabulary\Models\UserWord;
use App\Modules\Vocabulary\Models\Word;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[UseFactory(UserFactory::class)]
final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    public const AVATAR_DISK = 'avatars';

    /** How long a phone stays signed in without signing in again. */
    private const TOKEN_DAYS = 90;

    // is_admin, password, blocked_at and the avatar paths are deliberately not mass assignable.
    protected $fillable = ['google_id', 'telegram_id', 'telegram_username', 'email', 'name', 'google_avatar_url'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'is_admin' => false,
        'blocked_at' => null,
        'avatar_path' => null,
        'google_avatar_url' => null,
        'telegram_id' => null,
        'telegram_username' => null,
        'telegram_avatar_path' => null,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'blocked_at' => 'datetime',
            'telegram_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::forceDeleted(function (self $user): void {
            Storage::disk(self::AVATAR_DISK)->deleteDirectory($user->id);
            // A polymorphic relation has no foreign key to cascade through.
            $user->notifications()->delete();
        });
    }

    /** The in-app inbox, newest first. Overrides Notifiable's to use the prunable model. */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    /** The uploaded photo, otherwise the one from Telegram or Google. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $path = $this->avatar_path ?? $this->telegram_avatar_path;

            return $path !== null ? Storage::disk(self::AVATAR_DISK)->url($path) : $this->google_avatar_url;
        });
    }

    /** How the admin panel tells people apart: the email, or the Telegram @username. */
    protected function contact(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->email
            ?? ($this->telegram_username !== null ? '@'.$this->telegram_username : ($this->telegram_id !== null ? 'Telegram' : null)));
    }

    /** A new API session for one phone. */
    public function issueApiToken(string $device): string
    {
        return $this->createToken($device, ['*'], now()->addDays(self::TOKEN_DAYS))->plainTextToken;
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

    /** The words on the learner's list, with their schedule as `pivot`. */
    public function words(): BelongsToMany
    {
        return $this->belongsToMany(Word::class, 'user_words')
            ->using(UserWord::class)
            ->withPivot(['id', 'translation_word_id', 'status', 'ease', 'interval_days', 'repetitions', 'lapses', 'due_at', 'last_reviewed_at', 'source', 'context_sentence', 'form'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /** The same rows as `words()`, as models of their own: what sync and reviews work with. */
    public function userWords(): HasMany
    {
        return $this->hasMany(UserWord::class);
    }
}

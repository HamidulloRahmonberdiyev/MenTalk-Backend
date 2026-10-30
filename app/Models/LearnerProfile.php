<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gender;
use App\Enums\InterfaceLanguage;
use App\Enums\Level;
use Database\Factories\LearnerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LearnerProfile extends Model
{
    /** @use HasFactory<LearnerProfileFactory> */
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'level' => Level::class,
            'ui_language' => InterfaceLanguage::class,
            'goals' => 'array',
            'birth_date' => 'date',
            'last_active_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // The database default is the historical 'ru'; new learners get the configured default.
        self::creating(function (self $profile): void {
            $profile->target_language ??= config('languages.default');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function describe(): string
    {
        return collect([
            $this->level ? "level: {$this->level->value}" : null,
            $this->goals ? 'goals: '.implode(', ', $this->goals) : null,
            $this->country ? "country: {$this->country}" : null,
            $this->birth_date ? 'age: '.$this->birth_date->age : null,
        ])->filter()->implode('; ') ?: 'no details provided';
    }

    public function interfaceLanguage(): InterfaceLanguage
    {
        return $this->ui_language ?? InterfaceLanguage::Uzbek;
    }
}

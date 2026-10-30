<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ScenarioUnavailable;
use Database\Factories\ScenarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Scenario extends Model
{
    /** @use HasFactory<ScenarioFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gradient' => 'array',
            'title' => 'array',
            'subtitle' => 'array',
            'active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** One per language the scenario can be played in. */
    public function briefs(): HasMany
    {
        return $this->hasMany(ScenarioBrief::class);
    }

    public function isPlayableIn(string $language): bool
    {
        return $this->briefs()->where('language', $language)->exists();
    }

    /** @throws ScenarioUnavailable */
    public function briefFor(string $language): ScenarioBrief
    {
        return $this->briefs()->where('language', $language)->first()
            ?? throw new ScenarioUnavailable($this->id, $language);
    }
}

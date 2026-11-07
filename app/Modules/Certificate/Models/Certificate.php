<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Models;

use App\Modules\Account\Enums\Level;
use App\Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Certificate extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'level' => Level::class,
            'placement_score' => 'integer',
            'speaking' => 'float',
            'vocabulary' => 'float',
            'grammar' => 'float',
            'overall' => 'float',
            'conversations' => 'integer',
            'words' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** What the level rests on, for the documents: "a placement test, 3 tutor-rated conversations and 40 saved words". */
    public function basis(): string
    {
        $parts = array_values(array_filter([
            $this->placement_score !== null ? 'a placement test' : null,
            $this->conversations > 0 ? $this->conversations.' tutor-rated '.str('conversation')->plural($this->conversations) : null,
            $this->words > 0 ? $this->words.' saved '.str('word')->plural($this->words) : null,
        ]));

        return count($parts) > 1 ? implode(', ', array_slice($parts, 0, -1)).' and '.end($parts) : ($parts[0] ?? 'in-app activity');
    }

    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->serial);
    }
}

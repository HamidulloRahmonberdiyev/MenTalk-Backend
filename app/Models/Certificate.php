<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Level;
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
            'issued_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifyUrl(): string
    {
        return route('certificates.verify', $this->serial);
    }
}

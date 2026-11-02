<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExampleSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WordExample extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['source' => ExampleSource::class];
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}

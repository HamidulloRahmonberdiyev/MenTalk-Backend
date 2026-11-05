<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\DTO;

use App\Modules\Vocabulary\Models\UserWord;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class SyncResult
{
    /** @param Collection<int, UserWord> $words */
    public function __construct(public Collection $words, public CarbonInterface $cursor) {}
}

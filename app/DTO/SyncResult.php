<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final readonly class SyncResult
{
    public function __construct(public Collection $cards, public CarbonInterface $cursor) {}
}

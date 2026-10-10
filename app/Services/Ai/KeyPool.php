<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Exceptions\AiUnavailable;
use Illuminate\Support\Facades\Cache;

final readonly class KeyPool
{
    public function __construct(private array $keys, private int $cooldown) {}

    public function acquire(): string
    {
        $count = count($this->keys);

        Cache::add('ai:keys:cursor', 0);
        $start = (int) Cache::increment('ai:keys:cursor');

        for ($i = 0; $i < $count; $i++) {
            $key = $this->keys[($start + $i) % $count];

            if (! Cache::has($this->coolingKey($key))) {
                return $key;
            }
        }

        throw new AiUnavailable;
    }

    public function cool(string $key): void
    {
        Cache::put($this->coolingKey($key), 1, $this->cooldown);
    }

    private function coolingKey(string $key): string
    {
        return 'ai:keys:cool:'.substr(hash('sha256', $key), 0, 12);
    }
}

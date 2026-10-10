<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\DTO\CardChange;
use App\DTO\SyncResult;
use App\Models\User;
use App\Models\VocabCard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class SyncCards
{
    private const PAGE = 500;

    private const UPDATABLE = ['translation', 'example', 'form', 'stage', 'streak', 'lapses', 'due_at', 'updated_at', 'synced_at', 'deleted_at'];

    /** @param Collection<int, CardChange> $changes */
    public function __invoke(User $user, ?CarbonImmutable $cursor, Collection $changes): SyncResult
    {
        $now = CarbonImmutable::now();
        $existing = $user->cards()->withTrashed()->whereIn('word', $changes->pluck('word'))->get()->keyBy('word');

        $rows = $changes
            ->filter(fn (CardChange $change) => $existing->get($change->word)?->updated_at?->lt($change->updatedAt) ?? true)
            ->map(fn (CardChange $change) => $change->toRow($user->id, $existing->get($change->word)?->id ?? (string) Str::ulid(), $now))
            ->values()->all();

        if ($rows !== []) {
            VocabCard::upsert($rows, ['user_id', 'word'], self::UPDATABLE);
        }

        $cards = $user->cards()->withTrashed()
            ->when($cursor, fn ($query) => $query->where('synced_at', '>', $cursor))
            ->where('synced_at', '<=', $now)
            ->orderBy('synced_at')->limit(self::PAGE)->get();

        return new SyncResult($cards, $cards->count() === self::PAGE ? $cards->last()->synced_at : $now);
    }
}

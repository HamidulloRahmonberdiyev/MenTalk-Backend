<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Services;

use App\Modules\Account\Models\User;
use App\Modules\Vocabulary\Models\UserWord;
use Illuminate\Support\Collection;

/**
 * Finds the learner's rows for words a client names. Clients send the row id once they know it;
 * before the first sync they only know the spelling and the language.
 */
final readonly class UserWordLookup
{
    public function __construct(private WordNormalizer $normalizer) {}

    /**
     * Loads every row the references can point at, deleted ones included, in one query.
     *
     * @param  Collection<int, object{id: ?string, language: string, word: string}>  $references
     * @return callable(?string $id, string $language, string $word): ?UserWord
     */
    public function load(User $user, Collection $references): callable
    {
        $ids = $references->pluck('id')->filter()->values();
        $normalized = $references->map(fn (object $ref) => $this->normalizer->normalize($ref->word, $ref->language))->unique()->values();

        $rows = $user->userWords()->withTrashed()->with(['word', 'translation'])
            ->where(fn ($query) => $query->whereIn('id', $ids)->orWhereHas('word', fn ($word) => $word->whereIn('normalized', $normalized)))
            ->get();

        $byId = $rows->keyBy('id');
        $bySpelling = $rows->keyBy(fn (UserWord $row) => "{$row->word->language}|{$row->word->normalized}");

        return fn (?string $id, string $language, string $word): ?UserWord => ($id !== null ? $byId->get($id) : null)
            ?? $bySpelling->get($language.'|'.$this->normalizer->normalize($word, $language));
    }
}

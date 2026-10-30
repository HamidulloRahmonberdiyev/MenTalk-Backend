<?php

declare(strict_types=1);

namespace App\Services\Languages;

use App\Contracts\LanguageCatalog;
use App\DTO\TutorLanguage;
use App\Exceptions\UnsupportedLanguage;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;

/**
 * Languages from the database, cached as plain arrays until an admin edits one (see
 * `LanguageObserver`). The table is tiny and read on every AI request, so it is never queried twice.
 */
final readonly class CachedLanguageCatalog implements LanguageCatalog
{
    public const CACHE_KEY = 'languages';

    public function __construct(private string $defaultCode) {}

    public function available(): array
    {
        return array_values(array_filter($this->all(), fn (TutorLanguage $language) => $language->active));
    }

    public function isAvailable(string $code): bool
    {
        return ($this->all()[$code] ?? null)?->active === true;
    }

    public function get(string $code): TutorLanguage
    {
        return $this->all()[$code] ?? throw new UnsupportedLanguage($code);
    }

    public function default(): TutorLanguage
    {
        return $this->get($this->defaultCode);
    }

    /** @return array<string, TutorLanguage> */
    private function all(): array
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => Language::query()->orderBy('sort')->get()
            ->map(fn (Language $language) => $language->only([
                'code', 'name', 'tutor_name', 'voice', 'script_note', 'follow_ups', 'rules', 'basic_words', 'dictionary_form', 'active',
            ]))
            ->all());

        $languages = [];

        foreach ($rows as $row) {
            $languages[$row['code']] = TutorLanguage::fromArray($row);
        }

        return $languages;
    }
}

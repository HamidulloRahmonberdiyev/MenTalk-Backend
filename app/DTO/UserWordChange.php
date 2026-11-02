<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\WordSource;
use App\Enums\WordStatus;
use App\Support\ReviewState;
use Carbon\CarbonImmutable;

/** One word as a device last saw it. The newer `updatedAt` wins. */
final readonly class UserWordChange
{
    public function __construct(
        /** The server's row id; null until the device has synced the word once. */
        public ?string $id,
        public string $word,
        public string $language,
        public ?string $translation,
        public string $translationLanguage,
        public ?string $form,
        public ?string $contextSentence,
        public WordSource $source,
        public ReviewState $state,
        public ?CarbonImmutable $lastReviewedAt,
        public CarbonImmutable $updatedAt,
        public bool $deleted,
    ) {}

    /**
     * @param  string  $defaultLanguage  for a word without one
     * @param  string  $defaultTranslationLanguage  for a translation without one: the learner's interface language
     */
    public static function fromArray(array $data, string $defaultLanguage, string $defaultTranslationLanguage): self
    {
        return new self(
            id: $data['id'] ?? null,
            word: $data['word'],
            language: $data['language'] ?? $defaultLanguage,
            translation: $data['translation'] ?? null,
            translationLanguage: $data['translation_language'] ?? $defaultTranslationLanguage,
            form: $data['form'] ?? null,
            contextSentence: $data['context_sentence'] ?? null,
            source: WordSource::tryFrom($data['source'] ?? '') ?? WordSource::Manual,
            state: new ReviewState(
                WordStatus::from($data['status']),
                (float) $data['ease'],
                (int) $data['interval_days'],
                (int) ($data['repetitions'] ?? 0),
                (int) ($data['lapses'] ?? 0),
                CarbonImmutable::parse($data['due_at']),
            ),
            lastReviewedAt: isset($data['last_reviewed_at']) ? CarbonImmutable::parse($data['last_reviewed_at']) : null,
            updatedAt: CarbonImmutable::parse($data['updated_at']),
            deleted: (bool) ($data['deleted'] ?? false),
        );
    }

    public function toNewWord(): NewWord
    {
        return new NewWord($this->language, $this->word, $this->translation, $this->translationLanguage, $this->contextSentence, $this->form);
    }
}

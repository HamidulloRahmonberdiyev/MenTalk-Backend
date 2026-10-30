<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\TutorLanguage;
use App\Exceptions\UnsupportedLanguage;

/** The languages the app teaches. Callers never know where they are stored. */
interface LanguageCatalog
{
    /**
     * Languages a learner can choose now, in display order.
     *
     * @return list<TutorLanguage>
     */
    public function available(): array;

    /** Whether new profiles, conversations and words may use this language. */
    public function isAvailable(string $code): bool;

    /**
     * Any known language, also a switched-off one, so conversations already held in it still finish.
     *
     * @throws UnsupportedLanguage
     */
    public function get(string $code): TutorLanguage;

    /** The language assumed when a client sends none. */
    public function default(): TutorLanguage;
}

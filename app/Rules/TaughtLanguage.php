<?php

declare(strict_types=1);

namespace App\Rules;

use App\Contracts\LanguageCatalog;
use App\Exceptions\UnsupportedLanguage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A language the app teaches. New choices (profile, a new conversation) need an active one;
 * data already made in a language that was switched off since (words, reviews) stays valid.
 */
final readonly class TaughtLanguage implements ValidationRule
{
    public function __construct(private LanguageCatalog $languages, private bool $includeSwitchedOff) {}

    public static function make(bool $includeSwitchedOff = false): self
    {
        return new self(app(LanguageCatalog::class), $includeSwitchedOff);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->accepts($value)) {
            $fail('The :attribute is not a language the app teaches.');
        }
    }

    private function accepts(string $code): bool
    {
        if (! $this->includeSwitchedOff) {
            return $this->languages->isAvailable($code);
        }

        try {
            $this->languages->get($code);

            return true;
        } catch (UnsupportedLanguage) {
            return false;
        }
    }
}

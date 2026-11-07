<?php

declare(strict_types=1);

namespace App\Modules\Translator\DTO;

use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Translator\Support\TranslatorLanguages;
use Normalizer;

/**
 * One translator request, reduced to what changes the answer. Two requests with the same `key()`
 * get the same stored translation, whoever sends them.
 */
final readonly class TranslationQuery
{
    /**
     * Bump when the prompt or the stored shape changes so that old rows must not be served: rows of
     * an older version are never matched again, and `model:prune` removes them once unused.
     */
    public const VERSION = 1;

    /** The text as matched: NFC, trimmed, runs of spaces collapsed. Case is kept ("Turkey" is not "turkey"). */
    public string $text;

    /** 'latin' or 'cyrillic'. Only kept when Uzbek can be in the answer, so other pairs share rows. */
    public string $uzScript;

    public function __construct(
        string $text,
        /** A code from `TranslatorLanguages`, or 'auto' to have it detected. */
        public string $source,
        public string $target,
        /** Part-of-speech labels and the usage note are written in it. */
        public InterfaceLanguage $uiLanguage,
        string $uzScript,
    ) {
        $text = Normalizer::normalize($text, Normalizer::FORM_C) ?: $text;
        $text = (string) preg_replace('/[ \t\x{00A0}]+/u', ' ', str_replace("\r\n", "\n", $text));
        $this->text = trim($text);
        $this->uzScript = $this->mayInvolveUzbek() ? $uzScript : 'latin';
    }

    public function key(): string
    {
        return hash('sha256', implode("\x1F", [
            self::VERSION, $this->source, $this->target, $this->uiLanguage->value, $this->uzScript, $this->text,
        ]));
    }

    public function isAuto(): bool
    {
        return $this->source === TranslatorLanguages::AUTO;
    }

    /** One word (hyphens and apostrophes allowed): worth an entry in the global dictionary too. */
    public function isSingleWord(): bool
    {
        return mb_strlen($this->text) <= 64 && preg_match("/^[\\p{L}\\p{M}]+(?:['\u{2019}\\-][\\p{L}\\p{M}]+)*$/u", $this->text) === 1;
    }

    private function mayInvolveUzbek(): bool
    {
        // Detected input may be Uzbek, or already in the target language and so translated into the
        // fallback, which can be Uzbek.
        return $this->isAuto()
            || in_array('uz', [$this->source, $this->target], true)
            || ($this->source === $this->target && TranslatorLanguages::fallbackFor($this->target) === 'uz');
    }
}

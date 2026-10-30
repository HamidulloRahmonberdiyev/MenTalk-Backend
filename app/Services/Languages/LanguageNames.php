<?php

declare(strict_types=1);

namespace App\Services\Languages;

use App\Contracts\LanguageCatalog;
use App\Enums\InterfaceLanguage;

/** English names of every language the app knows: its interface languages and the taught ones. */
final readonly class LanguageNames
{
    public function __construct(private LanguageCatalog $languages) {}

    public function of(string $code): ?string
    {
        if ($interface = InterfaceLanguage::tryFrom($code)) {
            return $interface->englishName();
        }

        return $this->languages->isAvailable($code) ? $this->languages->get($code)->name : null;
    }
}

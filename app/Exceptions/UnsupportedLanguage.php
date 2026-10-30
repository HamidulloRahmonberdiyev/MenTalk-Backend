<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class UnsupportedLanguage extends DomainException
{
    public function __construct(public readonly string $language)
    {
        parent::__construct("Language [{$language}] is not taught");
    }
}

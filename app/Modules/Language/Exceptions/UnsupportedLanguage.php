<?php

declare(strict_types=1);

namespace App\Modules\Language\Exceptions;

use App\Shared\Http\Problem;
use DomainException;
use Illuminate\Http\JsonResponse;

final class UnsupportedLanguage extends DomainException
{
    public function __construct(public readonly string $language)
    {
        parent::__construct("Language [{$language}] is not taught");
    }

    public function render(): JsonResponse
    {
        return Problem::make(422, 'unsupported_language');
    }
}

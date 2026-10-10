<?php

declare(strict_types=1);

namespace App\Support;

final class Text
{
    public static function withoutEmoji(string $text): string
    {
        $clean = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text) ?? $text;

        return trim((string) preg_replace('/\s{2,}/u', ' ', $clean));
    }
}

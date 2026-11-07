<?php

declare(strict_types=1);

namespace App\Modules\Speech\Services;

use App\Shared\Support\Text;

/**
 * Splits a line for voicing in two: the first sentence, which is synthesized and played right away,
 * and the rest, synthesized while the first one plays. Speech takes longer to make the longer the
 * text, so the learner hears the tutor after one sentence's wait instead of the whole reply's.
 *
 * Never more than two pieces: every piece is a request against the TTS rate limit.
 */
final class SpeechChunker
{
    /** Below this a reply is quick to voice whole, and splitting it would only cost a request. */
    private const SPLIT_FROM = 90;

    /** Shorter pieces ("Ой!") sound clipped on their own and trip the too-short check. */
    private const MIN_PIECE = 20;

    /** @return non-empty-list<string> */
    public static function split(string $text): array
    {
        $text = trim($text);

        if (mb_strlen(Text::withoutEmoji($text)) < self::SPLIT_FROM) {
            return [$text];
        }

        // Ends of sentences: punctuation, any closing quotes or brackets, then the space after them.
        preg_match_all('/[.!?…]+["»”)\]]*\s+/u', $text, $ends, PREG_OFFSET_CAPTURE);

        foreach ($ends[0] as [$match, $offset]) {
            $cut = $offset + strlen($match);
            $first = trim(substr($text, 0, $cut));
            $rest = trim(substr($text, $cut));

            if (mb_strlen(Text::withoutEmoji($first)) >= self::MIN_PIECE) {
                return mb_strlen(Text::withoutEmoji($rest)) >= self::MIN_PIECE ? [$first, $rest] : [$text];
            }
        }

        return [$text];
    }
}

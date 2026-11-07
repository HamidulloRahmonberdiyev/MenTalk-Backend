<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

/**
 * Reads one string field out of a JSON object while the object is still being written, so its text
 * can be passed on piece by piece. Feed it the whole text received so far, each time more arrives.
 * Only the first occurrence of the field is read; the schema orders fields so that is the top-level one.
 */
final class JsonStringTap
{
    /** Byte offset in the buffer where the next undecoded part of the value starts; null until found. */
    private ?int $cursor = null;

    private string $value = '';

    private bool $closed = false;

    public function __construct(private readonly string $field) {}

    /** Decodes what the buffer added to the value since the last call; returns just that new text. */
    public function feed(string $buffer): string
    {
        if ($this->closed) {
            return '';
        }

        if ($this->cursor === null) {
            if (! preg_match('/"'.preg_quote($this->field, '/').'"\s*:\s*"/', $buffer, $match, PREG_OFFSET_CAPTURE)) {
                return '';
            }
            $this->cursor = $match[0][1] + strlen($match[0][0]);
        }

        $length = strlen($buffer);
        $i = $this->cursor;
        $raw = '';

        while ($i < $length) {
            $char = $buffer[$i];

            if ($char === '"') {
                $this->closed = true;
                $i++;
                break;
            }

            if ($char !== '\\') {
                $raw .= $char;
                $i++;

                continue;
            }

            // An escape split across two pieces waits for the rest of it.
            $size = $this->escapeSize($buffer, $i);
            if ($size === null || $i + $size > $length) {
                break;
            }
            $raw .= substr($buffer, $i, $size);
            $i += $size;
        }

        // A character cut in half by the network waits for its other bytes.
        $held = 0;
        while ($held < 4 && $raw !== '' && ! mb_check_encoding($raw, 'UTF-8')) {
            $raw = substr($raw, 0, -1);
            $held++;
        }
        $this->cursor = $i - $held;
        if ($held === 4) {
            $this->cursor = $i;
            $raw = mb_scrub($raw.substr($buffer, $i - $held, $held), 'UTF-8');
        }

        if ($raw === '') {
            return '';
        }

        $text = json_decode('"'.$raw.'"');
        if (! is_string($text)) {
            return '';
        }
        $this->value .= $text;

        return $text;
    }

    public function closed(): bool
    {
        return $this->closed;
    }

    /** Everything decoded so far: the whole value once `closed()`. */
    public function value(): string
    {
        return $this->value;
    }

    /** Bytes taken by the escape at `$at`, or null while too little of it has arrived to tell. */
    private function escapeSize(string $buffer, int $at): ?int
    {
        $kind = $buffer[$at + 1] ?? null;

        if ($kind === null) {
            return null;
        }
        if ($kind !== 'u') {
            return 2;
        }

        $hex = substr($buffer, $at + 2, 4);
        if (strlen($hex) < 4) {
            return null;
        }

        // A high surrogate only makes a character together with the low one that follows it.
        $code = hexdec($hex);

        return $code >= 0xD800 && $code <= 0xDBFF ? 12 : 6;
    }
}

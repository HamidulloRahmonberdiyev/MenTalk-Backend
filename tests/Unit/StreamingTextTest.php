<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Conversation\Services\JsonStringTap;
use App\Modules\Speech\Services\SpeechChunker;
use PHPUnit\Framework\TestCase;

final class StreamingTextTest extends TestCase
{
    /** Feeds `$json` in pieces of `$size` bytes, the way a stream would, and collects what came out. */
    private function tap(string $field, string $json, int $size): array
    {
        $tap = new JsonStringTap($field);
        $buffer = '';
        $out = '';

        foreach (str_split($json, $size) as $piece) {
            $buffer .= $piece;
            $out .= $tap->feed($buffer);
        }

        return [$out, $tap];
    }

    public function test_tap_reads_a_field_split_at_every_possible_point(): void
    {
        $reply = "Привет! Как \"дела\"?\nВсё хорошо 😀 \\ end";
        $json = json_encode(['transcript' => 'reply: "x"', 'emotion' => 'happy', 'reply' => $reply, 'mistakes' => []]);
        $escaped = json_encode(['transcript' => 'a', 'reply' => $reply], JSON_UNESCAPED_UNICODE);

        foreach ([$json, $escaped] as $source) {
            foreach ([1, 2, 3, 5, 13, 1000] as $size) {
                [$out, $tap] = $this->tap('reply', $source, $size);

                $this->assertSame($reply, $out, "piece size {$size}");
                $this->assertTrue($tap->closed());
                $this->assertSame($reply, $tap->value());
            }
        }
    }

    public function test_tap_stays_open_until_the_value_ends(): void
    {
        [$out, $tap] = $this->tap('reply', '{"reply":"Hel', 4);

        $this->assertSame('Hel', $out);
        $this->assertFalse($tap->closed());
    }

    public function test_chunker_keeps_short_replies_whole(): void
    {
        $this->assertSame(['Отлично! Что будете пить?'], SpeechChunker::split('Отлично! Что будете пить?'));
    }

    public function test_chunker_splits_off_the_first_full_sentence(): void
    {
        $text = 'Ой! Отлично, вы уже почти готовы. Скажите, пожалуйста, что вы хотите заказать сегодня на обед?';

        $this->assertSame(
            ['Ой! Отлично, вы уже почти готовы.', 'Скажите, пожалуйста, что вы хотите заказать сегодня на обед?'],
            SpeechChunker::split($text),
        );
    }

    public function test_chunker_does_not_leave_a_tiny_tail(): void
    {
        $text = 'Это очень длинное первое предложение, в котором много слов и даже запятых, правда. Да!';

        $this->assertSame([$text], SpeechChunker::split($text));
    }
}

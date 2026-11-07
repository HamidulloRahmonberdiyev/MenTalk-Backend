<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Ai\Exceptions\AiOverloaded;
use App\Modules\Ai\Exceptions\AiRateLimited;
use App\Modules\Ai\Services\GeminiTransport;
use App\Modules\Ai\Services\KeyPool;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

final class GeminiStreamTest extends TestCase
{
    private array $sent = [];

    private function transport(Response ...$responses): GeminiTransport
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));

        return new GeminiTransport(new KeyPool(['k1'], 30), ['base_url' => 'https://ai.test/v1beta', 'timeout' => 25], $stack);
    }

    private static function event(array $parts): string
    {
        return 'data: '.json_encode(['candidates' => [['content' => ['parts' => $parts]]]])."\r\n\r\n";
    }

    public function test_stream_hands_over_answer_text_in_order_and_skips_thoughts(): void
    {
        $body = self::event([['text' => 'thinking…', 'thought' => true]])
            .self::event([['text' => '{"reply":"При']])
            .self::event([['text' => 'вет"}']]);
        $pieces = [];

        $this->transport(new Response(200, ['Content-Type' => 'text/event-stream'], $body))
            ->stream('m', ['contents' => []], function (string $piece) use (&$pieces): void {
                $pieces[] = $piece;
            });

        $this->assertSame(['{"reply":"При', 'вет"}'], $pieces);
        $this->assertSame('alt=sse', $this->sent[0]['request']->getUri()->getQuery());
        $this->assertStringEndsWith('models/m:streamGenerateContent', $this->sent[0]['request']->getUri()->getPath());
    }

    public function test_stream_errors_map_to_the_usual_exceptions(): void
    {
        $this->expectException(AiRateLimited::class);
        $this->transport(new Response(429))->stream('m', [], fn () => null);
    }

    public function test_server_errors_are_overload_so_the_fallback_model_runs(): void
    {
        $this->expectException(AiOverloaded::class);
        $this->transport(new Response(503))->generate('m', []);
    }

    public function test_an_error_inside_the_stream_is_an_overload(): void
    {
        $this->expectException(AiOverloaded::class);
        $this->transport(new Response(200, [], 'data: {"error":{"code":500}}'."\n\n"))->stream('m', [], fn () => null);
    }
}

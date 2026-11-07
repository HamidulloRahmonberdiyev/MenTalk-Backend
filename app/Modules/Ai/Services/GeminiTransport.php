<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Exceptions\AiOverloaded;
use App\Modules\Ai\Exceptions\AiRateLimited;
use App\Modules\Ai\Exceptions\AiRejected;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class GeminiTransport
{
    /** A stream may run long as a whole; it is a silence this long that means it is stuck. */
    private const STREAM_TIMEOUT = 60;

    private readonly Client $http;

    private readonly int $readTimeout;

    /** @param  HandlerStack|null  $handler  only for tests, to answer without the network */
    public function __construct(private readonly KeyPool $keys, array $config, ?HandlerStack $handler = null)
    {
        $this->readTimeout = (int) $config['timeout'];
        $this->http = new Client(array_filter([
            'base_uri' => rtrim($config['base_url'], '/').'/',
            'timeout' => $config['timeout'],
            'connect_timeout' => 3,
            'http_errors' => false,
            'headers' => ['Content-Type' => 'application/json'],
            'handler' => $handler,
        ], static fn ($value) => $value !== null));
    }

    public function generate(string $model, array $payload): array
    {
        $key = $this->keys->acquire();
        $response = $this->http->post("models/{$model}:generateContent", [
            'json' => $payload,
            'headers' => ['x-goog-api-key' => $key],
        ]);
        $this->ensureOk($response, $key);

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * `generate()` as server-sent events: `$onText` gets each piece of the answer's text as it is
     * written. A connection that breaks halfway counts as the model being unavailable.
     *
     * @param  callable(string): void  $onText
     */
    public function stream(string $model, array $payload, callable $onText): void
    {
        $key = $this->keys->acquire();
        $response = $this->http->post("models/{$model}:streamGenerateContent", [
            'query' => ['alt' => 'sse'],
            'json' => $payload,
            'headers' => ['x-goog-api-key' => $key, 'Accept' => 'text/event-stream'],
            'stream' => true,
            'timeout' => self::STREAM_TIMEOUT,
            'read_timeout' => $this->readTimeout,
        ]);
        $this->ensureOk($response, $key);

        $body = $response->getBody();
        $buffer = '';

        try {
            while (! $body->eof()) {
                // CRs never occur inside the JSON itself (they are escaped), so dropping them only normalises line ends.
                $buffer .= str_replace("\r", '', $body->read(8192));

                while (($end = strpos($buffer, "\n\n")) !== false) {
                    $this->deliver(substr($buffer, 0, $end), $onText);
                    $buffer = substr($buffer, $end + 2);
                }
            }
        } catch (RuntimeException $e) {
            throw new AiOverloaded('Stream interrupted', previous: $e);
        } finally {
            $body->close();
        }

        $this->deliver($buffer, $onText);
    }

    /** @param  callable(string): void  $onText */
    private function deliver(string $block, callable $onText): void
    {
        $lines = array_filter(explode("\n", $block), static fn (string $line) => str_starts_with($line, 'data:'));
        $data = implode("\n", array_map(static fn (string $line) => ltrim(substr($line, 5)), $lines));

        if ($data === '') {
            return;
        }

        $chunk = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        if (isset($chunk['error'])) {
            throw new AiOverloaded('Stream error '.($chunk['error']['code'] ?? ''));
        }

        foreach ($chunk['candidates'][0]['content']['parts'] ?? [] as $part) {
            // Thinking models stream their reasoning as parts marked `thought`; only the answer counts.
            if (isset($part['text']) && empty($part['thought'])) {
                $onText($part['text']);
            }
        }
    }

    private function ensureOk(ResponseInterface $response, string $key): void
    {
        $status = $response->getStatusCode();

        match (true) {
            $status === 200 => null,
            $status === 429 => $this->rateLimited($key),
            // Exception messages are strings: under strict types an int status here was a TypeError, and no fallback model ran.
            $status >= 500 => throw new AiOverloaded((string) $status, $status),
            default => throw new AiRejected((string) $status, $status),
        };
    }

    private function rateLimited(string $key): never
    {
        $this->keys->cool($key);

        throw new AiRateLimited;
    }
}

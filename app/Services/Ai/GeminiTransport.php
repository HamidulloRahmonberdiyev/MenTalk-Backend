<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Exceptions\AiOverloaded;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiRejected;
use GuzzleHttp\Client;

final class GeminiTransport
{
    private readonly Client $http;

    public function __construct(private readonly KeyPool $keys, array $config)
    {
        $this->http = new Client([
            'base_uri' => rtrim($config['base_url'], '/').'/',
            'timeout' => $config['timeout'],
            'connect_timeout' => 3,
            'http_errors' => false,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    public function generate(string $model, array $payload): array
    {
        $key = $this->keys->acquire();
        $response = $this->http->post("models/{$model}:generateContent", [
            'json' => $payload,
            'headers' => ['x-goog-api-key' => $key],
        ]);
        $status = $response->getStatusCode();

        return match (true) {
            $status === 200 => json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
            $status === 429 => $this->rateLimited($key),
            $status >= 500 => throw new AiOverloaded($status),
            default => throw new AiRejected((string) $status, $status),
        };
    }

    private function rateLimited(string $key): never
    {
        $this->keys->cool($key);

        throw new AiRateLimited;
    }
}

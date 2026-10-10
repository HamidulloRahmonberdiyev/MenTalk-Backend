<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class MeteredAiClient implements AiClient
{
    public function __construct(private AiClient $inner) {}

    public function json(AiRequest $request): array
    {
        return $this->measure('json', fn () => $this->inner->json($request));
    }

    public function text(AiRequest $request): string
    {
        return $this->measure('text', fn () => $this->inner->text($request));
    }

    private function measure(string $kind, callable $call): mixed
    {
        $start = hrtime(true);
        $status = 200;

        try {
            return $call();
        } catch (Throwable $e) {
            $status = 502;

            throw $e;
        } finally {
            $row = [
                'user_id' => auth()->id(),
                'kind' => $kind,
                'latency_ms' => intdiv(hrtime(true) - $start, 1_000_000),
                'status' => $status,
            ];
            dispatch(static fn () => DB::table('ai_usage')->insert($row))->afterResponse();
        }
    }
}

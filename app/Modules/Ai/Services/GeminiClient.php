<?php

declare(strict_types=1);

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Exceptions\AiUnavailable;

final readonly class GeminiClient implements AiClient
{
    public function __construct(private GeminiTransport $transport, private array $models) {}

    public function json(AiRequest $request): array
    {
        return json_decode($this->textOf($request), true, 512, JSON_THROW_ON_ERROR);
    }

    public function text(AiRequest $request): string
    {
        return trim($this->textOf($request));
    }

    public function streamJson(AiRequest $request, callable $onText): array
    {
        $text = '';

        foreach ([$request->model ?? $this->models['primary'], $this->models['fallback']] as $model) {
            try {
                $this->transport->stream($model, $request->payload(), static function (string $piece) use (&$text, $onText): void {
                    $text .= $piece;
                    $onText($piece);
                });

                return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            } catch (AiUnavailable $e) {
                // Part of the answer is already on the learner's screen: a second model would contradict it.
                if ($text !== '') {
                    throw $e;
                }
            }
        }

        throw new AiUnavailable;
    }

    private function textOf(AiRequest $request): string
    {
        $response = $this->generate($request);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function generate(AiRequest $request): array
    {
        foreach ([$request->model ?? $this->models['primary'], $this->models['fallback']] as $model) {
            try {
                return $this->transport->generate($model, $request->payload());
            } catch (AiUnavailable) {
                continue;
            }
        }

        throw new AiUnavailable;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Translator\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Services\PromptStore;
use Illuminate\Support\Facades\Cache;

final readonly class Translate
{
    public function __construct(private AiClient $ai, private PromptStore $prompts) {}

    /** @param string $languageName English name of the target language ("Russian") */
    public function __invoke(string $text, string $languageName): string
    {
        $key = 'translate:'.sha1("{$languageName}|{$text}");

        return Cache::remember($key, now()->addDays(30), fn () => $this->ai->text(new AiRequest(
            system: strtr($this->prompts->get('translate'), ['{language}' => $languageName]),
            contents: [['role' => 'user', 'parts' => [['text' => $text]]]],
            temperature: 0.1,
        )));
    }
}

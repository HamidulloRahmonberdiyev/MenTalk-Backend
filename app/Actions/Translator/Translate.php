<?php

declare(strict_types=1);

namespace App\Actions\Translator;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use App\Services\Ai\PromptStore;
use Illuminate\Support\Facades\Cache;

final readonly class Translate
{
    public function __construct(private AiClient $ai, private PromptStore $prompts) {}

    public function __invoke(string $text, string $language): string
    {
        $key = 'translate:'.sha1("{$language}|{$text}");

        return Cache::remember($key, now()->addDays(30), fn () => $this->ai->text(new AiRequest(
            system: strtr($this->prompts->get('translate'), ['{language}' => $this->languageName($language)]),
            contents: [['role' => 'user', 'parts' => [['text' => $text]]]],
            temperature: 0.1,
        )));
    }

    private function languageName(string $language): string
    {
        return match ($language) {
            'ru' => 'Russian',
            'en' => 'English',
            default => 'Uzbek',
        };
    }
}

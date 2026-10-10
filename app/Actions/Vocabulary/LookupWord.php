<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use App\Services\Ai\PromptStore;
use App\Services\Ai\Schemas\LookupSchema;
use Illuminate\Support\Facades\Cache;

final readonly class LookupWord
{
    public function __construct(private AiClient $ai, private PromptStore $prompts) {}

    public function __invoke(string $word, string $sentence, string $language): array
    {
        $key = 'lookup:'.sha1(mb_strtolower($word)."|{$sentence}|{$language}");

        return Cache::remember($key, now()->addDays(90), fn () => $this->ai->json(new AiRequest(
            system: strtr($this->prompts->get('lookup'), ['{gloss}' => $this->gloss($language)]),
            contents: [['role' => 'user', 'parts' => [['text' => "Word: {$word}\nSentence: {$sentence}"]]]],
            schema: LookupSchema::get(),
            temperature: 0.1,
        )));
    }

    private function gloss(string $language): string
    {
        return match ($language) {
            'ru' => 'a very short, simple Russian explanation or synonym',
            'en' => 'English, one to three words',
            default => 'Uzbek, one to three words',
        };
    }
}

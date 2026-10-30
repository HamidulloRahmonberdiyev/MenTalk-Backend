<?php

declare(strict_types=1);

namespace App\Actions\Vocabulary;

use App\Contracts\AiClient;
use App\DTO\AiRequest;
use App\DTO\TutorLanguage;
use App\Enums\InterfaceLanguage;
use App\Services\Ai\Gloss;
use App\Services\Ai\PromptRenderer;
use App\Services\Ai\Schemas\LookupSchema;
use Illuminate\Support\Facades\Cache;

final readonly class LookupWord
{
    public function __construct(private AiClient $ai, private PromptRenderer $prompts) {}

    /** Shared by all learners: the same word in the same sentence never reaches the AI twice. */
    public function __invoke(string $word, string $sentence, InterfaceLanguage $interface, TutorLanguage $language): array
    {
        $key = 'lookup:'.sha1(mb_strtolower($word)."|{$sentence}|{$interface->value}|{$language->code}");

        return Cache::remember($key, now()->addDays(90), fn () => $this->ai->json(new AiRequest(
            system: $this->prompts->render('lookup', $language, ['{gloss}' => Gloss::for($interface, $language)]),
            contents: [['role' => 'user', 'parts' => [['text' => "Word: {$word}\nSentence: {$sentence}"]]]],
            schema: LookupSchema::get(),
            temperature: 0.1,
        )));
    }
}

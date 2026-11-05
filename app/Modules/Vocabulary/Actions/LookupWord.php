<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Services\Gloss;
use App\Modules\Ai\Services\PromptRenderer;
use App\Modules\Language\DTO\TutorLanguage;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Vocabulary\Schemas\LookupSchema;
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

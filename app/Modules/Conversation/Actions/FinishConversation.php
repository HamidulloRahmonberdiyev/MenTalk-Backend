<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Actions;

use App\Modules\Ai\Contracts\AiClient;
use App\Modules\Ai\DTO\AiRequest;
use App\Modules\Ai\Services\Gloss;
use App\Modules\Ai\Services\PromptRenderer;
use App\Modules\Conversation\Enums\ConversationState;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\ConversationResult;
use App\Modules\Conversation\Schemas\EvaluationSchema;
use App\Modules\Conversation\Services\Transcript;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Progress\Services\ProgressTracker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class FinishConversation
{
    public function __construct(
        private AiClient $ai,
        private PromptRenderer $prompts,
        private LanguageCatalog $languages,
        private ProgressTracker $progress,
    ) {}

    public function __invoke(Conversation $conversation): ConversationResult
    {
        return Cache::lock("finish:{$conversation->id}", 60)->block(5, function () use ($conversation) {
            $conversation->refresh();

            return $conversation->result()->first() ?? $this->finish($conversation);
        });
    }

    private function finish(Conversation $conversation): ConversationResult
    {
        $scores = $conversation->learner_turns === 0 ? $this->empty() : $this->evaluate($conversation);
        $seconds = (int) $conversation->started_at->diffInSeconds(now());
        $user = $conversation->user()->first();

        return DB::transaction(function () use ($conversation, $scores, $seconds, $user) {
            $result = $conversation->result()->create($scores);
            $conversation->update([
                'state' => ConversationState::Finished,
                'ended_at' => now(),
                'duration_sec' => $seconds,
                'score' => $scores['overall'],
            ]);
            $this->progress->record($user, minutes: intdiv($seconds + 59, 60), xp: 0);

            return $result;
        });
    }

    private function evaluate(Conversation $conversation): array
    {
        $conversation->loadMissing('user.profile');
        $language = $this->languages->get($conversation->target_language);
        $interface = $conversation->user->profile?->interfaceLanguage() ?? InterfaceLanguage::Uzbek;

        $raw = $this->ai->json(new AiRequest(
            system: $this->prompts->render('evaluation', $language, ['{gloss}' => Gloss::for($interface, $language)]),
            contents: [['role' => 'user', 'parts' => [['text' => Transcript::of($conversation)]]]],
            schema: EvaluationSchema::get(),
            temperature: 0.2,
        ));

        $score = fn (string $key) => max(0, min(10, (int) round($raw[$key] ?? 0)));

        return [
            'speech' => $score('speech'),
            'vocabulary' => $score('vocabulary'),
            'grammar' => $score('grammar'),
            'overall' => (int) round(($score('speech') + $score('vocabulary') + $score('grammar')) / 3),
            'mistakes' => array_slice($raw['mistakes'] ?? [], 0, 3),
            'new_words' => array_slice($raw['newWords'] ?? [], 0, 5),
        ];
    }

    private function empty(): array
    {
        return ['speech' => 0, 'vocabulary' => 0, 'grammar' => 0, 'overall' => 0, 'mistakes' => [], 'new_words' => []];
    }
}

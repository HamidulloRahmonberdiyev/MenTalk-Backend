<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Enums\TurnRole;
use App\Modules\Conversation\Events\LearnerMadeMistakes;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;
use Illuminate\Support\Facades\DB;

final class TurnHistory
{
    private const WINDOW = 24;

    public function contents(Conversation $conversation): array
    {
        $contents = $conversation->turns()
            ->latest('id')->limit(self::WINDOW)->get(['id', 'role', 'text'])
            ->reverse()
            ->map(fn (Turn $turn) => ['role' => $turn->role->geminiRole(), 'parts' => [['text' => $turn->text]]])
            ->values()->all();

        $startsWithTutor = ($contents[0]['role'] ?? null) === 'model';

        return $startsWithTutor
            ? [['role' => 'user', 'parts' => [['text' => '(the learner joins the conversation)']]], ...$contents]
            : $contents;
    }

    /**
     * The tutor's line already saved for this request, when the app sends it again because the answer
     * never reached it: the opening once there is one, or the answer to the turn with the same client id.
     */
    public function answered(Conversation $conversation, TurnInput $input): ?Turn
    {
        $tutor = $conversation->turns()->where('role', TurnRole::Tutor);

        return match (true) {
            $input->kind === TurnKind::Opening => $tutor->oldest('id')->first(),
            $input->clientId !== null => $tutor->where('client_id', $input->clientId)->first(),
            default => null,
        };
    }

    /** What the learner said just before this tutor line; empty when the line answered no one (the opening). */
    public function learnerLineBefore(Turn $tutor): string
    {
        $previous = Turn::query()
            ->where('conversation_id', $tutor->conversation_id)
            ->where('id', '<', $tutor->id)
            ->latest('id')
            ->first(['role', 'text']);

        return $previous?->role === TurnRole::Learner ? $previous->text : '';
    }

    public function append(Conversation $conversation, TurnInput $input, TurnResult $result, ?string $audioUrl): Turn
    {
        $learnerSaid = $input->kind === TurnKind::Text ? $input->text : $result->transcript;
        $learnerSpoke = $input->kind !== TurnKind::Opening && $learnerSaid !== '';

        $turn = DB::transaction(function () use ($conversation, $input, $result, $audioUrl, $learnerSaid, $learnerSpoke) {
            if ($learnerSpoke) {
                $conversation->turns()->create([
                    'role' => TurnRole::Learner,
                    'text' => $learnerSaid,
                    'mistakes' => $result->mistakes,
                ]);
                $conversation->increment('learner_turns');
            }

            return $conversation->turns()->create([
                'role' => TurnRole::Tutor,
                'client_id' => $input->clientId,
                'text' => $result->reply,
                'emotion' => $result->emotion,
                'audio_url' => $audioUrl,
            ]);
        });

        if ($learnerSpoke && $result->mistakes !== []) {
            event(new LearnerMadeMistakes(
                $conversation->user_id,
                $conversation->target_language,
                $conversation->id,
                $conversation->user?->profile?->interfaceLanguage()->value ?? 'uz',
                $result->mistakes,
            ));
        }

        return $turn;
    }
}

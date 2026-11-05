<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Services;

use App\Modules\Conversation\DTO\TurnInput;
use App\Modules\Conversation\DTO\TurnResult;
use App\Modules\Conversation\Enums\TurnKind;
use App\Modules\Conversation\Enums\TurnRole;
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

    public function append(Conversation $conversation, TurnInput $input, TurnResult $result, ?string $audioUrl): Turn
    {
        return DB::transaction(function () use ($conversation, $input, $result, $audioUrl) {
            $learnerSaid = $input->kind === TurnKind::Text ? $input->text : $result->transcript;

            if ($input->kind !== TurnKind::Opening && $learnerSaid !== '') {
                $conversation->turns()->create([
                    'role' => TurnRole::Learner,
                    'text' => $learnerSaid,
                    'mistakes' => $result->mistakes,
                ]);
                $conversation->increment('learner_turns');
            }

            return $conversation->turns()->create([
                'role' => TurnRole::Tutor,
                'text' => $result->reply,
                'emotion' => $result->emotion,
                'audio_url' => $audioUrl,
            ]);
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Enums\TurnRole;
use App\Modules\Conversation\Http\Requests\TurnSpeechRequest;
use App\Modules\Conversation\Models\Conversation;
use App\Modules\Conversation\Models\Turn;
use App\Modules\Conversation\Services\TutorPersona;
use App\Modules\Speech\Contracts\SpeechSynthesizer;
use App\Shared\Http\Controller;
use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * The voice of one tutor line, asked for again when it never reached the app (a piece that failed, a
 * stream cut short). `text` voices only that part of the line (the rest after a piece already heard);
 * it has to be taken from the line, so this can never voice anything the tutor did not say.
 */
final class TurnSpeechController extends Controller
{
    public function __invoke(TurnSpeechRequest $request, Conversation $conversation, Turn $turn, SpeechSynthesizer $speech, TutorPersona $persona): JsonResponse
    {
        Gate::authorize('view', $conversation);
        abort_unless($turn->role === TurnRole::Tutor, 404);

        $text = $request->spokenPartOf($turn);

        if ($text === null) {
            return Problem::make(422, 'not_in_line');
        }

        try {
            $clip = $speech->speak($text, $persona->voice($conversation));
        } catch (Throwable $e) {
            report($e);

            return Problem::make(503, 'speech_unavailable', ['Retry-After' => '3']);
        }

        // The whole line, voiced at last: kept for the history, like a voice that came the first time.
        if ($text === $turn->text && $turn->audio_url === null) {
            $turn->update(['audio_url' => $clip->url]);
        }

        return response()->json(['data' => ['url' => $clip->url, 'ms' => $clip->durationMs]]);
    }
}

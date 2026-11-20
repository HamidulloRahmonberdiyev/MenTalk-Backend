<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Http\Requests\TutorVoiceLanguageRequest;
use App\Modules\Conversation\Models\TutorVoice;
use App\Modules\Conversation\Services\VoiceSample;
use App\Shared\Http\Controller;
use App\Shared\Http\Problem;
use Illuminate\Http\JsonResponse;
use Throwable;

/** A short greeting in the voice, so the learner can hear it before picking it. */
final class TutorVoiceSampleController extends Controller
{
    public function __invoke(TutorVoiceLanguageRequest $request, TutorVoice $voice, VoiceSample $sample): JsonResponse
    {
        abort_unless($voice->active, 404);

        try {
            $clip = $sample($voice, $request->language());
        } catch (Throwable $e) {
            report($e);

            return Problem::make(503, 'speech_unavailable', ['Retry-After' => '3']);
        }

        return response()->json(['data' => ['url' => $clip->url, 'ms' => $clip->durationMs]]);
    }
}

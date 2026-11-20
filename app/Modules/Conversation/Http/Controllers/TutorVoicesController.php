<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Http\Requests\TutorVoiceLanguageRequest;
use App\Modules\Conversation\Models\TutorVoice;
use App\Modules\Conversation\Services\TutorPersona;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

/**
 * The tutor voices a learner can pick, in the order admins set. `default` marks the one a conversation
 * in that language gets when the learner picked none, so the app can show it as chosen.
 */
final class TutorVoicesController extends Controller
{
    public function __invoke(TutorVoiceLanguageRequest $request, TutorPersona $persona): JsonResponse
    {
        $default = $persona->defaultVoice($request->language());
        $defaultMarked = false;

        $voices = TutorVoice::query()->active()->orderBy('sort')->orderBy('id')->get()
            ->map(function (TutorVoice $voice) use ($default, &$defaultMarked) {
                // Two list entries may share a Gemini voice: only the first is the default.
                $isDefault = ! $defaultMarked && $voice->voice === $default;
                $defaultMarked = $defaultMarked || $isDefault;

                return [
                    'id' => $voice->id,
                    'name' => $voice->name,
                    'gender' => $voice->gender->value,
                    'default' => $isDefault,
                ];
            });

        return response()->json(['data' => $voices]);
    }
}

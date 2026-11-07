<?php

declare(strict_types=1);

namespace App\Modules\Pronunciation\Http\Controllers;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Pronunciation\Actions\NextPhrases;
use App\Modules\Pronunciation\Http\Requests\PhrasesRequest;
use App\Modules\Pronunciation\Models\Phrase;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

/** `POST /pronunciation/phrases/next`: the next lines to read aloud, none the learner was given before while others remain. */
final class PhrasesController extends Controller
{
    public function __invoke(PhrasesRequest $request, NextPhrases $next, LanguageCatalog $languages): JsonResponse
    {
        $phrases = $next($request->user(), $request->language($languages), $request->level(), $request->limit());

        return response()->json(['data' => $phrases->map(fn (Phrase $phrase) => [
            'id' => $phrase->id,
            'text' => $phrase->text,
            'level' => $phrase->level->value,
        ])->values()]);
    }
}

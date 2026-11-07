<?php

declare(strict_types=1);

namespace App\Modules\Translator\Http\Controllers;

use App\Modules\Translator\Actions\FindOrTranslate;
use App\Modules\Translator\Http\Requests\TranslationRequest;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

/** The translator screen: a full translation (meanings, alternatives, note), stored ones first. */
final class TranslationController extends Controller
{
    public function __invoke(TranslationRequest $request, FindOrTranslate $translate): JsonResponse
    {
        [$result, $stored] = $translate($request->translationQuery(), $request->user());

        return response()->json(['data' => $result, 'meta' => ['stored' => $stored]]);
    }
}

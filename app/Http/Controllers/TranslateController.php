<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Translator\Translate;
use App\Http\Requests\TranslateRequest;
use Illuminate\Http\JsonResponse;

final class TranslateController extends Controller
{
    public function __invoke(TranslateRequest $request, Translate $translate): JsonResponse
    {
        return response()->json(['data' => [
            'translation' => $translate($request->string('text')->toString(), $request->languageName()),
        ]]);
    }
}

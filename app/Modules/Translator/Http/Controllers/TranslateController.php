<?php

declare(strict_types=1);

namespace App\Modules\Translator\Http\Controllers;

use App\Modules\Translator\Actions\Translate;
use App\Modules\Translator\Http\Requests\TranslateRequest;
use App\Shared\Http\Controller;
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

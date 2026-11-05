<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Vocabulary\Actions\LookupWord;
use App\Modules\Vocabulary\Http\Requests\LookupWordRequest;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class LookupWordController extends Controller
{
    public function __invoke(LookupWordRequest $request, LookupWord $lookup, LanguageCatalog $languages): JsonResponse
    {
        return response()->json(['data' => $lookup(
            $request->string('word')->toString(),
            $request->string('sentence')->toString(),
            $request->interfaceLanguage(),
            $request->targetLanguage($languages),
        )]);
    }
}

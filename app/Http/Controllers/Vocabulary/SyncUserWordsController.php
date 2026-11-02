<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vocabulary;

use App\Actions\Vocabulary\SyncUserWords;
use App\Contracts\LanguageCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncUserWordsRequest;
use App\Http\Resources\UserWordResource;
use App\Services\Vocabulary\WordNormalizer;
use Illuminate\Http\JsonResponse;

final class SyncUserWordsController extends Controller
{
    public function __invoke(SyncUserWordsRequest $request, SyncUserWords $sync, LanguageCatalog $languages, WordNormalizer $normalizer): JsonResponse
    {
        $result = $sync($request->user(), $request->cursor(), $request->changes($languages, $normalizer));

        return response()->json(['data' => [
            'words' => UserWordResource::collection($result->words)->resolve(),
            'cursor' => $result->cursor->toIso8601String(),
        ]]);
    }
}

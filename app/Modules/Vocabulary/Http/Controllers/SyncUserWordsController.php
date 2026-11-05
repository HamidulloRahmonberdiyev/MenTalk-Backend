<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Vocabulary\Actions\SyncUserWords;
use App\Modules\Vocabulary\Http\Requests\SyncUserWordsRequest;
use App\Modules\Vocabulary\Http\Resources\UserWordResource;
use App\Modules\Vocabulary\Services\WordNormalizer;
use App\Shared\Http\Controller;
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

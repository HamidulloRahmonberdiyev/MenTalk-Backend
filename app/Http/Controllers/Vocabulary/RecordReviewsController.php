<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vocabulary;

use App\Actions\Vocabulary\RecordReviews;
use App\Contracts\LanguageCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordReviewsRequest;
use Illuminate\Http\JsonResponse;

final class RecordReviewsController extends Controller
{
    public function __invoke(RecordReviewsRequest $request, RecordReviews $record, LanguageCatalog $languages): JsonResponse
    {
        return response()->json(['data' => [
            'xp' => $record($request->user(), $request->answers($languages)),
        ]]);
    }
}

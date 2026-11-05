<?php

declare(strict_types=1);

namespace App\Modules\Vocabulary\Http\Controllers;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Vocabulary\Actions\RecordReviews;
use App\Modules\Vocabulary\Http\Requests\RecordReviewsRequest;
use App\Shared\Http\Controller;
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

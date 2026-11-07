<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Http\Controllers;

use App\Modules\Mistakes\Actions\RecordMistakeReviews;
use App\Modules\Mistakes\Http\Requests\RecordMistakeReviewsRequest;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

/** `POST /mistakes/reviews`: a finished drill round. Resending the same round is harmless. */
final class RecordMistakeReviewsController extends Controller
{
    public function __invoke(RecordMistakeReviewsRequest $request, RecordMistakeReviews $record): JsonResponse
    {
        return response()->json(['data' => [
            'xp' => $record($request->user(), $request->answers()),
        ]]);
    }
}

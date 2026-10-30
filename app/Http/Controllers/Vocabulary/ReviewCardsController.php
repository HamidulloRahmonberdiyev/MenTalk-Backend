<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vocabulary;

use App\Actions\Vocabulary\ReviewCards;
use App\Contracts\LanguageCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewCardsRequest;
use Illuminate\Http\JsonResponse;

final class ReviewCardsController extends Controller
{
    public function __invoke(ReviewCardsRequest $request, ReviewCards $review, LanguageCatalog $languages): JsonResponse
    {
        return response()->json(['data' => [
            'xp' => $review($request->user(), $request->answers($languages)),
        ]]);
    }
}

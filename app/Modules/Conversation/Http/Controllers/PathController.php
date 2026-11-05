<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Controllers;

use App\Modules\Conversation\Http\Requests\ShowPathRequest;
use App\Modules\Conversation\Services\LearningPath;
use App\Modules\Language\Contracts\LanguageCatalog;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class PathController extends Controller
{
    public function __invoke(ShowPathRequest $request, LanguageCatalog $languages, LearningPath $path): JsonResponse
    {
        $user = $request->user();
        $language = $request->language()
            ?? $user->profile()->value('target_language')
            ?? $languages->default()->code;

        return response()->json(['data' => $path->for($user, $language)]);
    }
}

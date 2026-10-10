<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vocabulary;

use App\Actions\Vocabulary\LookupWord;
use App\Http\Controllers\Controller;
use App\Http\Requests\LookupWordRequest;
use Illuminate\Http\JsonResponse;

final class LookupWordController extends Controller
{
    public function __invoke(LookupWordRequest $request, LookupWord $lookup): JsonResponse
    {
        return response()->json(['data' => $lookup(
            $request->string('word')->toString(),
            $request->string('sentence')->toString(),
            $request->string('language')->toString(),
        )]);
    }
}

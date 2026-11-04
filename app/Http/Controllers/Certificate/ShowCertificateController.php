<?php

declare(strict_types=1);

namespace App\Http\Controllers\Certificate;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\Certificate\CertificateAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** What the certificate screen shows: the progress towards one, and the latest one already issued. */
final class ShowCertificateController extends Controller
{
    public function __invoke(Request $request, CertificateAssessment $assess): JsonResponse
    {
        $standing = $assess($request->user());

        $certificate = Certificate::query()
            ->where('user_id', $request->user()->id)
            ->where('language', $standing->language)
            ->latest('issued_at')
            ->first();

        return response()->json(['data' => [
            'language' => $standing->language,
            'level' => $standing->level?->value,
            'cefr' => $standing->level?->cefr(),
            'placed' => $standing->isPlaced(),
            'conversations' => $standing->conversations,
            'required_conversations' => $standing->requiredConversations,
            'scores' => $standing->scores,
            'eligible' => $standing->isEligible(),
            'certificate' => $certificate ? CertificateResource::make($certificate)->resolve($request) : null,
        ]]);
    }
}

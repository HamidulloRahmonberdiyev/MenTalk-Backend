<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Http\Resources\CertificateResource;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Certificate\Services\CertificateAssessment;
use App\Shared\Http\Controller;
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

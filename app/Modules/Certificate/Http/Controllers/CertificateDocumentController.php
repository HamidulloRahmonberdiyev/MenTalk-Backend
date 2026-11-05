<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Models\Certificate;
use App\Modules\Certificate\Services\CertificateDocument;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The page the app prints to a PDF. */
final class CertificateDocumentController extends Controller
{
    public function __invoke(Request $request, string $serial, CertificateDocument $document): JsonResponse
    {
        $certificate = Certificate::query()->where('serial', $serial)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(['data' => ['html' => $document->html($certificate)]]);
    }
}

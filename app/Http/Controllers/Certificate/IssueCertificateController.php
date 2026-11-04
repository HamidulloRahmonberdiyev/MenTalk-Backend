<?php

declare(strict_types=1);

namespace App\Http\Controllers\Certificate;

use App\Actions\Certificate\IssueCertificate;
use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use Illuminate\Http\Request;

final class IssueCertificateController extends Controller
{
    public function __invoke(Request $request, IssueCertificate $issue): CertificateResource
    {
        return new CertificateResource($issue($request->user()));
    }
}

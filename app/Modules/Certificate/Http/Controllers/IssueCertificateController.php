<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Actions\IssueCertificate;
use App\Modules\Certificate\Http\Resources\CertificateResource;
use App\Shared\Http\Controller;
use Illuminate\Http\Request;

final class IssueCertificateController extends Controller
{
    public function __invoke(Request $request, IssueCertificate $issue): CertificateResource
    {
        return new CertificateResource($issue($request->user()));
    }
}

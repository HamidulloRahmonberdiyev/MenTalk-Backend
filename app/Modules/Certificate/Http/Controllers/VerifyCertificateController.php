<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Models\Certificate;
use App\Shared\Http\Controller;
use Illuminate\Contracts\View\View;

/** The public page behind the QR code: anyone holding a certificate can check that it is genuine. */
final class VerifyCertificateController extends Controller
{
    public function __invoke(string $serial): View
    {
        $certificate = Certificate::query()->where('serial', $serial)->whereHas('user')->first();

        abort_if($certificate === null, 404);

        return view('certificates.verify', ['certificate' => $certificate]);
    }
}

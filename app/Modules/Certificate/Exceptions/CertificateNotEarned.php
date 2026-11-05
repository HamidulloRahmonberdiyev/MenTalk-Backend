<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Exceptions;

use App\Shared\Http\Problem;
use DomainException;
use Illuminate\Http\JsonResponse;

/** The learner has not met the requirements yet; the app shows what is missing. */
final class CertificateNotEarned extends DomainException
{
    public function render(): JsonResponse
    {
        return Problem::make(422, 'certificate_not_earned');
    }
}

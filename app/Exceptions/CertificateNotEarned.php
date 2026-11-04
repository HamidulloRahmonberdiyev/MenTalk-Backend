<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/** The learner has not met the requirements yet; the app shows what is missing. */
final class CertificateNotEarned extends DomainException {}

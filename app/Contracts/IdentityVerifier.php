<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\Identity;

interface IdentityVerifier
{
    public function verify(string $idToken): Identity;
}

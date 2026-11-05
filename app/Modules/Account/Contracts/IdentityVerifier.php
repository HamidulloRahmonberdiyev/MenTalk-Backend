<?php

declare(strict_types=1);

namespace App\Modules\Account\Contracts;

use App\Modules\Account\DTO\Identity;

interface IdentityVerifier
{
    public function verify(string $idToken): Identity;
}

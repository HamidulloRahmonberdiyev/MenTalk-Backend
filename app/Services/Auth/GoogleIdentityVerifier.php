<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\IdentityVerifier;
use App\DTO\Identity;
use Google\Client;
use Illuminate\Auth\AuthenticationException;

final readonly class GoogleIdentityVerifier implements IdentityVerifier
{
    public function __construct(private array $clientIds) {}

    public function verify(string $idToken): Identity
    {
        foreach ($this->clientIds as $clientId) {
            $payload = (new Client(['client_id' => $clientId]))->verifyIdToken($idToken);

            if ($payload) {
                return new Identity($payload['sub'], $payload['email'], $payload['name'] ?? $payload['email']);
            }
        }

        throw new AuthenticationException('Invalid Google token');
    }
}

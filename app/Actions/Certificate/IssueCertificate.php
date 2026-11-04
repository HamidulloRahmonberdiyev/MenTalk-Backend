<?php

declare(strict_types=1);

namespace App\Actions\Certificate;

use App\Exceptions\CertificateNotEarned;
use App\Models\Certificate;
use App\Models\User;
use App\Services\Certificate\CertificateAssessment;

final readonly class IssueCertificate
{
    /** No 0/O or 1/I: the number gets typed in by hand on the verification page. */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(private CertificateAssessment $assess) {}

    /** Returns the certificate for the learner's current level, issuing it the first time. */
    public function __invoke(User $user): Certificate
    {
        $standing = ($this->assess)($user);

        if (! $standing->isEligible()) {
            throw new CertificateNotEarned;
        }

        return Certificate::query()->createOrFirst(
            ['user_id' => $user->id, 'language' => $standing->language, 'level' => $standing->level],
            [
                'serial' => self::serial(),
                'holder_name' => $user->name,
                'placement_score' => $standing->placementScore,
                ...$standing->scores,
                'conversations' => $standing->conversations,
                'issued_at' => now(),
            ],
        );
    }

    private static function serial(): string
    {
        $pick = fn () => implode('', array_map(
            fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)],
            range(1, 4),
        ));

        return 'MT-'.now()->format('Y').'-'.$pick().'-'.$pick();
    }
}

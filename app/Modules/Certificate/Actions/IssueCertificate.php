<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Actions;

use App\Modules\Account\Models\User;
use App\Modules\Certificate\Exceptions\CertificateNotEarned;
use App\Modules\Certificate\Models\Certificate;
use App\Modules\Certificate\Services\CertificateAssessment;
use Illuminate\Support\Facades\Cache;

/**
 * Issues a certificate for where the learner stands right now, whenever they ask. An issued
 * certificate never changes (it is a dated record, verifiable by its QR code), so new progress means
 * a new certificate; asking again with nothing changed returns the current one instead of a copy.
 */
final readonly class IssueCertificate
{
    /** No 0/O or 1/I: the number gets typed in by hand on the verification page. */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(private CertificateAssessment $assess) {}

    public function __invoke(User $user): Certificate
    {
        // A double tap must not issue two certificates for the same results.
        return Cache::lock("certificate:{$user->id}", 10)->block(5, function () use ($user) {
            $standing = ($this->assess)($user);

            if (! $standing->isEligible()) {
                throw new CertificateNotEarned;
            }

            $latest = Certificate::query()
                ->where(['user_id' => $user->id, 'language' => $standing->language])
                ->latest('issued_at')->latest('id')
                ->first();

            if ($latest !== null && self::snapshotOf($latest) === $standing->snapshot()) {
                return $latest;
            }

            return Certificate::query()->create([
                'user_id' => $user->id,
                'language' => $standing->language,
                'serial' => self::serial(),
                'holder_name' => $user->name,
                ...$standing->snapshot(),
                'issued_at' => now(),
            ]);
        });
    }

    /** The same keys and types as `CertificateStanding::snapshot()`. */
    private static function snapshotOf(Certificate $certificate): array
    {
        $score = fn (?float $value) => $value === null ? null : round($value, 1);

        return [
            'level' => $certificate->level->value,
            'placement_score' => $certificate->placement_score,
            'speaking' => $score($certificate->speaking),
            'vocabulary' => $score($certificate->vocabulary),
            'grammar' => $score($certificate->grammar),
            'overall' => $score($certificate->overall),
            'conversations' => $certificate->conversations,
            'words' => $certificate->words,
        ];
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

<?php

declare(strict_types=1);

namespace App\Modules\Account\DTO;

final readonly class Identity
{
    public function __construct(
        public string $subject,
        public string $email,
        public string $name,
        public ?string $picture = null,
    ) {}
}

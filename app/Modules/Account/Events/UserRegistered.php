<?php

declare(strict_types=1);

namespace App\Modules\Account\Events;

use App\Modules\Account\Models\User;

final readonly class UserRegistered
{
    public function __construct(public User $user) {}
}

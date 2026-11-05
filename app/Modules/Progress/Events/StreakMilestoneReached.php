<?php

declare(strict_types=1);

namespace App\Modules\Progress\Events;

use App\Modules\Account\Models\User;

final readonly class StreakMilestoneReached
{
    public function __construct(public User $user, public int $days) {}
}

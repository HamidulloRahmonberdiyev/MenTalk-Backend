<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Notifications\StreakMilestone;
use App\Modules\Progress\Events\StreakMilestoneReached;

final class SendStreakMilestone
{
    public function handle(StreakMilestoneReached $event): void
    {
        $event->user->notify(new StreakMilestone($event->days));
    }
}

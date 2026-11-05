<?php

declare(strict_types=1);

namespace App\Modules\Notification;

use App\Modules\Account\Events\UserRegistered;
use App\Modules\Notification\Console\Commands\SendDailyReminders;
use App\Modules\Notification\Listeners\SendStreakMilestone;
use App\Modules\Notification\Listeners\SendWelcome;
use App\Modules\Progress\Events\StreakMilestoneReached;
use App\Shared\Providers\ModuleServiceProvider;

final class NotificationServiceProvider extends ModuleServiceProvider
{
    protected array $listeners = [
        UserRegistered::class => SendWelcome::class,
        StreakMilestoneReached::class => SendStreakMilestone::class,
    ];

    protected array $consoleCommands = [SendDailyReminders::class];
}

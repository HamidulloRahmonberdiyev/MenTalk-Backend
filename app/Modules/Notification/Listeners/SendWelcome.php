<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Account\Events\UserRegistered;
use App\Modules\Notification\Notifications\Welcome;

final class SendWelcome
{
    public function handle(UserRegistered $event): void
    {
        $event->user->notify(new Welcome);
    }
}

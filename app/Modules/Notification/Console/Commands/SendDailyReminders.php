<?php

declare(strict_types=1);

namespace App\Modules\Notification\Console\Commands;

use App\Modules\Notification\Services\DailyReminders;
use Illuminate\Console\Command;

final class SendDailyReminders extends Command
{
    protected $signature = 'notifications:remind {--hour= : Treat this local hour as the reminder hour (testing)}';

    protected $description = 'Send the daily streak / word-review reminders to learners whose local reminder hour it is';

    public function handle(DailyReminders $reminders): int
    {
        $sent = $reminders->send((int) ($this->option('hour') ?? config('notifications.reminder_hour')));

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}

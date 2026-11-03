<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Models\LearnerProfile;
use App\Models\User;
use App\Models\UserWord;
use App\Notifications\InboxNotification;
use App\Notifications\StreakAtRisk;
use App\Notifications\WordsDue;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs every hour and reaches the learners for whom it is now the reminder hour in their own
 * timezone. Each gets at most one nudge a day: keeping the streak first, otherwise due words.
 * Nobody who has already practised today, or has been away for weeks, is bothered.
 */
final class SendDailyReminders extends Command
{
    protected $signature = 'notifications:remind {--hour= : Treat this local hour as the reminder hour (testing)}';

    protected $description = 'Send the daily streak / word-review reminders to learners whose local reminder hour it is';

    private const CHUNK = 500;

    public function handle(): int
    {
        $hour = (int) ($this->option('hour') ?? config('notifications.reminder_hour'));
        $now = CarbonImmutable::now();
        $sent = 0;

        LearnerProfile::query()
            ->select(['user_id', 'timezone', 'streak', 'last_active_on'])
            ->whereNotNull('last_active_on')
            // Generous on purpose: the exact cut-off is checked per learner in their own timezone.
            ->where('last_active_on', '>=', $now->subDays((int) config('notifications.remind_inactive_days') + 1)->toDateString())
            ->whereHas('user', fn ($user) => $user->whereNull('blocked_at'))
            ->chunkById(self::CHUNK, function (Collection $profiles) use ($hour, $now, &$sent) {
                $due = $profiles->filter(fn (LearnerProfile $profile) => $this->localNow($profile, $now)?->hour === $hour);

                if ($due->isEmpty()) {
                    return;
                }

                $sent += $this->remind($due, $now);
            }, 'user_id');

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    /** @param  Collection<int, LearnerProfile>  $profiles */
    private function remind(Collection $profiles, CarbonImmutable $now): int
    {
        $ids = $profiles->pluck('user_id');

        $alreadyReminded = DB::table('notifications')
            ->whereIn('notifiable_id', $ids)
            ->whereIn('type', array_map(fn (NotificationType $type) => $type->value, NotificationType::REMINDERS))
            ->where('created_at', '>=', $now->subHours(20))
            ->pluck('notifiable_id')
            ->flip();

        $dueWords = UserWord::query()
            ->whereIn('user_id', $ids)
            ->where('due_at', '<=', $now)
            ->groupBy('user_id')
            ->havingRaw('count(*) >= ?', [(int) config('notifications.min_due_words')])
            ->selectRaw('user_id, count(*) as due')
            ->pluck('due', 'user_id');

        $users = User::query()->whereIn('id', $ids)->get()->keyBy('id');
        $sent = 0;

        foreach ($profiles as $profile) {
            $user = $users->get($profile->user_id);

            if ($user === null || $alreadyReminded->has($profile->user_id)) {
                continue;
            }

            $notification = $this->pick($profile, $this->localNow($profile, $now), (int) ($dueWords[$profile->user_id] ?? 0));

            if ($notification !== null) {
                $user->notify($notification);
                $sent++;
            }
        }

        return $sent;
    }

    private function pick(LearnerProfile $profile, ?CarbonImmutable $localNow, int $dueWords): ?InboxNotification
    {
        if ($localNow === null) {
            return null;
        }

        // Calendar days compared as strings: `last_active_on` is already a day in the learner's timezone.
        $lastActive = $profile->last_active_on->toDateString();
        $today = $localNow->toDateString();
        $cutoff = $localNow->subDays((int) config('notifications.remind_inactive_days'))->toDateString();

        if ($lastActive >= $today || $lastActive < $cutoff) {
            return null;
        }

        if ($profile->streak > 0 && $lastActive === $localNow->subDay()->toDateString()) {
            return new StreakAtRisk($profile->streak);
        }

        return $dueWords > 0 ? new WordsDue($dueWords) : null;
    }

    private function localNow(LearnerProfile $profile, CarbonImmutable $now): ?CarbonImmutable
    {
        try {
            return $now->setTimezone($profile->timezone ?: config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Puts an announcement into every matching learner's inbox, in bulk inserts rather than one
 * `notify()` per user. Safe to retry: each row's id is derived from the announcement and the
 * learner, so a second run inserts only the rows the first one did not get to.
 */
final class SendAnnouncement implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private const CHUNK = 1000;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public readonly int $announcementId) {}

    public function uniqueId(): string
    {
        return (string) $this->announcementId;
    }

    public function handle(): void
    {
        $announcement = Announcement::query()->find($this->announcementId);

        if ($announcement === null || $announcement->sent_at !== null) {
            return;
        }

        $data = json_encode($announcement->message()->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $morph = (new User)->getMorphClass();
        $now = now();
        $sent = 0;

        User::query()
            ->whereNull('blocked_at')
            ->when($announcement->audience, fn ($users, $language) => $users->whereHas(
                'profile', fn ($profile) => $profile->where('ui_language', $language->value),
            ))
            ->select('id')
            ->chunkById(self::CHUNK, function (Collection $users) use ($announcement, $data, $morph, $now, &$sent) {
                DB::table('notifications')->insertOrIgnore($users->map(fn (User $user) => [
                    'id' => Uuid::uuid5(Uuid::NAMESPACE_URL, "mentalk:announcement:{$announcement->id}:{$user->id}")->toString(),
                    'type' => NotificationType::Announcement->value,
                    'notifiable_type' => $morph,
                    'notifiable_id' => $user->id,
                    'data' => $data,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
                // Counted by learner, not by inserted row: after a retry the earlier rows are already there.
                $sent += $users->count();
            });

        $announcement->forceFill(['sent_at' => now(), 'recipients' => $sent])->save();
    }
}

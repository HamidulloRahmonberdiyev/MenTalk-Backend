<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Notifications\DatabaseNotification as BaseNotification;

/** A row of the learner's inbox; `model:prune` removes old ones. */
final class DatabaseNotification extends BaseNotification
{
    use MassPrunable;

    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subDays((int) config('notifications.retention_days')));
    }

    /** Only rows this API knows how to show (the table could also hold, say, Filament's own). */
    public function scopeForInbox(Builder $query): void
    {
        $query->whereIn('type', NotificationType::values());
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::fromArray($this->data);
    }
}

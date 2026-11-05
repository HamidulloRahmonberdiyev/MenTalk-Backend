<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Modules\Account\Models\User;
use App\Modules\Language\Enums\InterfaceLanguage;
use App\Modules\Notification\DTO\NotificationMessage;
use App\Modules\Notification\Enums\NotificationAction;
use App\Modules\Notification\Enums\NotificationKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A message an admin writes once and sends to every learner (or those of one interface language). */
final class Announcement extends Model
{
    protected $fillable = ['kind', 'title', 'body', 'action', 'audience'];

    protected $attributes = ['kind' => 'news', 'recipients' => 0];

    protected function casts(): array
    {
        return [
            'kind' => NotificationKind::class,
            'action' => NotificationAction::class,
            'audience' => InterfaceLanguage::class,
            'title' => 'array',
            'body' => 'array',
            'dispatched_at' => 'datetime',
            'sent_at' => 'datetime',
            'recipients' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Once sent, the text is in the learners' inboxes and the record no longer changes. */
    public function isDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }

    public function message(): NotificationMessage
    {
        return NotificationMessage::written($this->kind, $this->title ?? [], $this->body ?? [], $this->action);
    }
}

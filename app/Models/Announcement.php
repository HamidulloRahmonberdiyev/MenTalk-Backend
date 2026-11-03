<?php

declare(strict_types=1);

namespace App\Models;

use App\DTO\NotificationMessage;
use App\Enums\InterfaceLanguage;
use App\Enums\NotificationAction;
use App\Enums\NotificationKind;
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

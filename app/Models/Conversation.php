<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationState;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUlids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'state' => ConversationState::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function turns(): HasMany
    {
        return $this->hasMany(Turn::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ConversationResult::class);
    }
}

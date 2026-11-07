<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Modules\Account\Models\User;
use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupportTicket extends Model
{
    protected $guarded = [];

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'status' => TicketStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

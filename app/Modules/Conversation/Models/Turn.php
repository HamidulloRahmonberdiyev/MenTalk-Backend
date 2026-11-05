<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Models;

use App\Modules\Conversation\Enums\TurnRole;
use Illuminate\Database\Eloquent\Model;

final class Turn extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'conversation_turns';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'role' => TurnRole::class,
            'mistakes' => 'array',
        ];
    }
}

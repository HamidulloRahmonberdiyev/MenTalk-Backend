<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ConversationResult extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'conversation_id';

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'mistakes' => 'array',
            'new_words' => 'array',
        ];
    }
}

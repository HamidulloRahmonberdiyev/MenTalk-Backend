<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VocabCard extends Model
{
    use HasUlids, SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'updated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}

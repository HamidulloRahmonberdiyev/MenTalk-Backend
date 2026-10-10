<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ScenarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Scenario extends Model
{
    /** @use HasFactory<ScenarioFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'gradient' => 'array',
            'title' => 'array',
            'subtitle' => 'array',
            'active' => 'boolean',
        ];
    }
}

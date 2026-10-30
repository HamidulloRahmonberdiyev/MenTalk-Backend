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

    /** A word is unique per language: the same spelling in two languages is two cards. */
    public static function identityOf(string $language, string $word): string
    {
        return "{$language}|{$word}";
    }

    public function identity(): string
    {
        return self::identityOf($this->language, $this->word);
    }
}

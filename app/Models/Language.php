<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A language the app teaches. Managed in the admin panel; read through `LanguageCatalog`. */
final class Language extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'active' => 'boolean',
        ];
    }

    public function briefs(): HasMany
    {
        return $this->hasMany(ScenarioBrief::class, 'language', 'code');
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Language\Models;

use Illuminate\Database\Eloquent\Model;

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
}

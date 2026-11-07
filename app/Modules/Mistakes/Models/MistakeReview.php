<?php

declare(strict_types=1);

namespace App\Modules\Mistakes\Models;

use Illuminate\Database\Eloquent\Model;

/** One drill answer. Append-only; the same mistake at the same instant is the same answer. */
final class MistakeReview extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}

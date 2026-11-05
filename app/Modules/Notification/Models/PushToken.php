<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Modules\Account\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

final class PushToken extends Model
{
    protected $fillable = ['user_id', 'personal_access_token_id', 'token', 'platform'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    /**
     * Devices worth sending to: the account is usable and the session has not run out. An expired
     * session keeps its row until pruned, yet the person on that phone is already signed out.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDeliverable(Builder $query): void
    {
        $query
            ->whereHas('user', fn ($user) => $user->whereNull('blocked_at'))
            ->whereHas('session', fn ($session) => $session->where(
                fn ($valid) => $valid->whereNull('expires_at')->orWhere('expires_at', '>', now()),
            ));
    }
}

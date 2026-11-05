<?php

declare(strict_types=1);

namespace App\Modules\Account;

use App\Modules\Account\Console\Commands\CreateAdmin;
use App\Modules\Account\Contracts\IdentityVerifier;
use App\Modules\Account\Jobs\PurgeUserData;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\GoogleIdentityVerifier;
use App\Shared\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class AccountServiceProvider extends ModuleServiceProvider
{
    /** The value already stored in `notifications.notifiable_type` and `personal_access_tokens.tokenable_type`. */
    private const USER_MORPH_TYPE = 'App\Models\User';

    /** The class name inside purges queued before the move to modules; they wait 30 days, so they outlive a deploy. */
    private const LEGACY_PURGE_JOB = 'App\Jobs\PurgeUserData';

    protected array $consoleCommands = [CreateAdmin::class];

    public function register(): void
    {
        $this->app->bind(IdentityVerifier::class, fn () => new GoogleIdentityVerifier(config('ai.google_client_ids')));

        if (! class_exists(self::LEGACY_PURGE_JOB, autoload: false)) {
            class_alias(PurgeUserData::class, self::LEGACY_PURGE_JOB);
        }
    }

    public function boot(): void
    {
        parent::boot();

        Relation::morphMap([self::USER_MORPH_TYPE => User::class]);

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}

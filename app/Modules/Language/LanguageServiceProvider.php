<?php

declare(strict_types=1);

namespace App\Modules\Language;

use App\Modules\Language\Contracts\LanguageCatalog;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Observers\LanguageObserver;
use App\Modules\Language\Services\CachedLanguageCatalog;
use App\Shared\Providers\ModuleServiceProvider;

final class LanguageServiceProvider extends ModuleServiceProvider
{
    protected array $observers = [Language::class => LanguageObserver::class];

    public function register(): void
    {
        // Holds only configuration; the language data itself lives in the cache, so a singleton is safe under Octane.
        $this->app->singleton(LanguageCatalog::class, fn () => new CachedLanguageCatalog(config('languages.default')));
    }
}

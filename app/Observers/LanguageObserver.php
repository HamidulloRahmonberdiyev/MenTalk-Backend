<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Language;
use App\Services\Languages\CachedLanguageCatalog;
use Illuminate\Support\Facades\Cache;

final class LanguageObserver
{
    public function saved(Language $language): void
    {
        $this->forget();
    }

    public function deleted(Language $language): void
    {
        $this->forget();
    }

    /** The bootstrap lists the languages, so it goes stale with them. */
    private function forget(): void
    {
        Cache::forget(CachedLanguageCatalog::CACHE_KEY);
        Cache::forget('bootstrap');
    }
}

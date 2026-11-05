<?php

declare(strict_types=1);

namespace App\Modules\Language\Observers;

use App\Modules\Language\Models\Language;
use App\Modules\Language\Services\CachedLanguageCatalog;
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

    private function forget(): void
    {
        Cache::forget(CachedLanguageCatalog::CACHE_KEY);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Bootstrap\Services;

use App\Modules\Account\Enums\Experience;
use App\Modules\Account\Enums\Focus;
use App\Modules\Account\Enums\Level;
use App\Modules\Account\Enums\ReferralSource;
use App\Modules\Conversation\Http\Resources\ScenarioResource;
use App\Modules\Conversation\Models\Scenario;
use App\Modules\Language\Http\Resources\LanguageResource;
use App\Modules\Language\Models\Language;
use Illuminate\Support\Facades\Cache;

/** Everything the app needs before the learner signs in, cached until an admin changes it. */
final class BootstrapPayload
{
    public const CACHE_KEY = 'bootstrap';

    /** @return array<string, mixed> */
    public function get(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => [
            'scenarios' => ScenarioResource::collection(
                Scenario::query()->where('active', true)->with(['briefs' => fn ($query) => $query->select(['id', 'scenario_id', 'language'])->orderBy('id')])->orderBy('sort')->get(),
            )->resolve(),
            'languages' => LanguageResource::collection(Language::query()->where('active', true)->orderBy('sort')->get())->resolve(),
            'default_language' => config('languages.default'),
            'levels' => array_column(Level::cases(), 'value'),
            'goals' => ['travel', 'work', 'study', 'relocation', 'family', 'fun'],
            'experiences' => array_column(Experience::cases(), 'value'),
            'referral_sources' => array_column(ReferralSource::cases(), 'value'),
            'focus' => array_column(Focus::cases(), 'value'),
            'features' => config('features'),
            'min_app_version' => config('app.min_version'),
        ]);
    }
}

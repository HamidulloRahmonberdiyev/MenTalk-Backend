<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Level;
use App\Http\Resources\LanguageResource;
use App\Http\Resources\ScenarioResource;
use App\Models\Language;
use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class BootstrapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(Cache::rememberForever('bootstrap', fn () => [
            'scenarios' => ScenarioResource::collection(
                Scenario::query()->where('active', true)->with(['briefs' => fn ($query) => $query->select(['id', 'scenario_id', 'language'])->orderBy('id')])->orderBy('sort')->get(),
            )->resolve(),
            'languages' => LanguageResource::collection(Language::query()->where('active', true)->orderBy('sort')->get())->resolve(),
            'default_language' => config('languages.default'),
            'levels' => array_column(Level::cases(), 'value'),
            'goals' => ['travel', 'work', 'study', 'relocation', 'family', 'fun'],
            'features' => config('features'),
            'min_app_version' => config('app.min_version'),
        ]));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Level;
use App\Http\Resources\ScenarioResource;
use App\Models\Scenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class BootstrapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(Cache::rememberForever('bootstrap', fn () => [
            'scenarios' => ScenarioResource::collection(Scenario::query()->where('active', true)->orderBy('sort')->get())->resolve(),
            'levels' => array_column(Level::cases(), 'value'),
            'goals' => ['travel', 'work', 'study', 'relocation', 'family', 'fun'],
            'features' => config('features'),
            'min_app_version' => config('app.min_version'),
        ]));
    }
}

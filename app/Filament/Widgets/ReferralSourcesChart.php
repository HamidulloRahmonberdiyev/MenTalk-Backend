<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ReferralSource;
use App\Models\LearnerProfile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class ReferralSourcesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Foydalanuvchilar qayerdan keldi';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $counts = Cache::remember('admin:referral-sources', 60, fn () => LearnerProfile::query()
            ->whereNotNull('referral_source')
            ->toBase()
            ->selectRaw('referral_source, count(*) as total')
            ->groupBy('referral_source')
            ->pluck('total', 'referral_source')
            ->all());

        $sources = collect(ReferralSource::cases())->filter(fn (ReferralSource $source) => isset($counts[$source->value]))->values();

        return [
            'datasets' => [[
                'label' => 'Foydalanuvchilar',
                'data' => $sources->map(fn (ReferralSource $source) => (int) $counts[$source->value])->all(),
                'backgroundColor' => $sources->map(fn (ReferralSource $source) => $source->chartColor())->all(),
            ]],
            'labels' => $sources->map(fn (ReferralSource $source) => $source->getLabel())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Admin\AiUsageReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AiUsageOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'AI (oxirgi 24 soat)';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $usage = app(AiUsageReport::class)->lastDay();

        return [
            Stat::make('So\'rovlar', number_format($usage['requests'])),
            Stat::make('Xatolar', $usage['error_rate'].'%')
                ->description(number_format($usage['errors']).' ta xato')
                ->color($usage['error_rate'] > 5 ? 'danger' : 'success'),
            Stat::make('O\'rtacha kechikish', number_format($usage['avg_ms']).' ms'),
            Stat::make('p95 kechikish', number_format($usage['p95_ms']).' ms')
                ->description('Maqsad: 8000 ms dan past')
                ->color($usage['p95_ms'] > 8000 ? 'danger' : 'success'),
        ];
    }
}

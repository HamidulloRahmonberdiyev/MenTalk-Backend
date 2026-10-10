<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Admin\AiUsageReport;
use Filament\Widgets\ChartWidget;

final class AiRequestsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'AI so\'rovlari (14 kun)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $daily = app(AiUsageReport::class)->daily(14);

        return [
            'datasets' => [
                ['label' => 'So\'rovlar', 'data' => $daily['requests'], 'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245, 158, 11, 0.15)', 'fill' => true],
                ['label' => 'Xatolar', 'data' => $daily['errors'], 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.15)', 'fill' => true],
            ],
            'labels' => $daily['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Conversation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class UsersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Foydalanuvchilar';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $numbers = Cache::remember('admin:users-overview', 60, fn () => [
            'total' => User::query()->count(),
            'week' => User::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'active' => DB::table('daily_progress')->where('day', now()->toDateString())->count(),
            'today' => Conversation::query()->where('started_at', '>=', now()->startOfDay())->count(),
        ]);

        return [
            Stat::make('Jami foydalanuvchilar', number_format($numbers['total'])),
            Stat::make('Oxirgi 7 kunda qo\'shilgan', number_format($numbers['week'])),
            Stat::make('Bugun faol', number_format($numbers['active']))->description('Mashq qilganlar'),
            Stat::make('Bugungi suhbatlar', number_format($numbers['today'])),
        ];
    }
}

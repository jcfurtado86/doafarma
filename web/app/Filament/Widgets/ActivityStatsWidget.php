<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Activitylog\Models\Activity;

class ActivityStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    #[\Override]
    protected function getStats(): array
    {
        $today     = now()->startOfDay();
        $thisWeek  = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            Stat::make('Atividades Hoje', Activity::where('created_at', '>=', $today)->count())
                ->description('Registros nas últimas 24h')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            Stat::make('Atividades na Semana', Activity::where('created_at', '>=', $thisWeek)->count())
                ->description('Últimos 7 dias')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
            Stat::make('Atividades no Mês', Activity::where('created_at', '>=', $thisMonth)->count())
                ->description('Mês atual')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}

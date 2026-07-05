<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationOffering;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Override;

class ReportsStatsWidget extends StatsOverviewWidget
{
    #[Override]
    protected static bool $isDiscovered = false;

    #[Override]
    protected ?string $pollingInterval = '30s';

    #[Override]
    protected function getStats(): array
    {
        $totalOfferings     = MedicationOffering::count();
        $completedOfferings = MedicationOffering::completed()->count();
        $totalQuantity      = (int) MedicationOffering::sum('quantity');

        $completionRate = $totalOfferings > 0
            ? round(($completedOfferings / $totalOfferings) * 100, 1)
            : 0;

        $thisMonth          = now()->startOfMonth();
        $lastMonth          = now()->subMonth()->startOfMonth();
        $lastMonthEnd       = now()->subMonth()->endOfMonth();
        $offeringsThisMonth = MedicationOffering::where('created_at', '>=', $thisMonth)->count();
        $offeringsLastMonth = MedicationOffering::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $growthRate = $offeringsLastMonth > 0
            ? round((($offeringsThisMonth - $offeringsLastMonth) / $offeringsLastMonth) * 100, 1)
            : ($offeringsThisMonth > 0 ? 100 : 0);

        return [
            Stat::make('Total de Ofertas', number_format($totalOfferings, 0, ',', '.'))
                ->description('Medicamentos cadastrados')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),

            Stat::make('Doações Concluídas', number_format($completedOfferings, 0, ',', '.'))
                ->description("Taxa de sucesso: {$completionRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Unidades Doadas', number_format($totalQuantity, 0, ',', '.'))
                ->description('Total de medicamentos')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Crescimento Mensal', ($growthRate >= 0 ? '+' : '') . $growthRate . '%')
                ->description("{$offeringsThisMonth} ofertas este mês")
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthRate >= 0 ? 'success' : 'danger'),
        ];
    }
}

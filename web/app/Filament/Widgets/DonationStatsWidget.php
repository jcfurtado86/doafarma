<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationOffering;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Override;

class DonationStatsWidget extends BaseWidget
{
    #[Override]
    protected static ?int $sort = 1;

    #[Override]
    protected ?string $pollingInterval = '30s';

    #[Override]
    protected function getStats(): array
    {
        $totalOfferings     = MedicationOffering::count();
        $availableOfferings = MedicationOffering::available()->count();
        $completedOfferings = MedicationOffering::completed()->count();
        $totalQuantity      = MedicationOffering::sum('quantity');

        $completionRate = $totalOfferings > 0
            ? round(($completedOfferings / $totalOfferings) * 100, 1)
            : 0;

        return [
            Stat::make('Total de Ofertas', number_format($totalOfferings, 0, ',', '.'))
                ->description('Medicamentos cadastrados')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary')
                ->chart($this->getOfferingsChartData()),

            Stat::make('Ofertas Disponíveis', number_format($availableOfferings, 0, ',', '.'))
                ->description('Aguardando solicitação')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Doações Concluídas', number_format($completedOfferings, 0, ',', '.'))
                ->description("Taxa de sucesso: {$completionRate}%")
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('info'),

            Stat::make('Unidades Doadas', number_format((int) $totalQuantity, 0, ',', '.'))
                ->description('Total de medicamentos')
                ->descriptionIcon('heroicon-m-cube')
                ->color('warning'),
        ];
    }

    /**
     * Get chart data for offerings over the last 7 days.
     *
     * @return array<int>
     */
    private function getOfferingsChartData(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date   = now()->subDays($i)->toDateString();
            $data[] = MedicationOffering::whereDate('created_at', $date)->count();
        }

        return $data;
    }
}

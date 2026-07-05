<?php

declare(strict_types = 1);

namespace App\Filament\Pages;

use App\Filament\Widgets\MonthlyBarChart;
use App\Filament\Widgets\OfferingsLineChart;
use App\Filament\Widgets\ReportsStatsWidget;
use App\Filament\Widgets\RequestsLineChart;
use App\Filament\Widgets\StatusDoughnutChart;
use Filament\Pages\Page;
use Override;

class Reports extends Page
{
    #[Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    #[Override]
    protected static ?string $navigationLabel = 'Relatórios';

    #[Override]
    protected static ?string $title = 'Relatórios e Métricas';

    #[Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Monitoramento';

    #[Override]
    protected static ?int $navigationSort = 2;

    /**
     * @return array<class-string>
     */
    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ReportsStatsWidget::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            OfferingsLineChart::class,
            RequestsLineChart::class,
            StatusDoughnutChart::class,
            MonthlyBarChart::class,
        ];
    }

    /**
     * @return int|array<string, int>
     */
    #[Override]
    public function getFooterWidgetsColumns(): int | array
    {
        return 2;
    }
}

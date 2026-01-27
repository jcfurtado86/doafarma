<?php

declare(strict_types = 1);

namespace App\Filament\Pages;

use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Relatórios e Métricas';

    protected static ?string $navigationGroup = 'Monitoramento';

    protected static ?int $navigationSort = 2;

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getViewData(): array
    {
        return [
            'offeringsData'  => $this->getOfferingsChartData(),
            'requestsData'   => $this->getRequestsChartData(),
            'statusData'     => $this->getStatusDistributionData(),
            'monthlyData'    => $this->getMonthlyDonationsData(),
            'summaryMetrics' => $this->getSummaryMetrics(),
        ];
    }

    /**
     * @return array{labels: array<string>, data: array<int>}
     */
    private function getOfferingsChartData(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[]   = MedicationOffering::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * @return array{labels: array<string>, data: array<int>}
     */
    private function getRequestsChartData(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[]   = MedicationRequest::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    /**
     * @return array{labels: array<string>, data: array<int>, colors: array<string>}
     */
    private function getStatusDistributionData(): array
    {
        return [
            'labels' => ['Disponíveis', 'Reservados', 'Concluídos'],
            'data'   => [
                MedicationOffering::available()->count(),
                MedicationOffering::reserved()->count(),
                MedicationOffering::completed()->count(),
            ],
            'colors' => ['#22c55e', '#eab308', '#3b82f6'],
        ];
    }

    /**
     * @return array{labels: array<string>, offerings: array<int>, completions: array<int>}
     */
    private function getMonthlyDonationsData(): array
    {
        $labels      = [];
        $offerings   = [];
        $completions = [];

        for ($i = 5; $i >= 0; $i--) {
            $date       = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd   = $date->copy()->endOfMonth();

            $labels[]      = $date->translatedFormat('M/Y');
            $offerings[]   = MedicationOffering::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $completions[] = MedicationAppointment::completed()
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->count();
        }

        return [
            'labels'      => $labels,
            'offerings'   => $offerings,
            'completions' => $completions,
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    private function getSummaryMetrics(): array
    {
        $totalOfferings        = MedicationOffering::count();
        $completedOfferings    = MedicationOffering::completed()->count();
        $totalQuantity         = (int) MedicationOffering::sum('quantity');
        $completedAppointments = MedicationAppointment::completed()->count();

        $completionRate = $totalOfferings > 0
            ? round(($completedOfferings / $totalOfferings) * 100, 1)
            : 0;

        $thisMonth    = now()->startOfMonth();
        $lastMonth    = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $offeringsThisMonth = MedicationOffering::where('created_at', '>=', $thisMonth)->count();
        $offeringsLastMonth = MedicationOffering::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $growthRate = $offeringsLastMonth > 0
            ? round((($offeringsThisMonth - $offeringsLastMonth) / $offeringsLastMonth) * 100, 1)
            : ($offeringsThisMonth > 0 ? 100 : 0);

        return [
            'totalOfferings'        => $totalOfferings,
            'completedOfferings'    => $completedOfferings,
            'completionRate'        => $completionRate,
            'totalQuantity'         => number_format($totalQuantity, 0, ',', '.'),
            'completedAppointments' => $completedAppointments,
            'offeringsThisMonth'    => $offeringsThisMonth,
            'growthRate'            => $growthRate,
            'growthRateFormatted'   => ($growthRate >= 0 ? '+' : '') . $growthRate . '%',
        ];
    }
}

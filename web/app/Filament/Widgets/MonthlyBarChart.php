<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use Filament\Widgets\ChartWidget;
use Override;

class MonthlyBarChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Tendência Mensal (6 meses)';

    protected ?string $maxHeight = '250px';

    protected int | string | array $columnSpan = 1;

    #[Override]
    protected function getData(): array
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
            'datasets' => [
                [
                    'label'           => 'Ofertas Criadas',
                    'data'            => $offerings,
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label'           => 'Entregas Concluídas',
                    'data'            => $completions,
                    'backgroundColor' => '#22c55e',
                ],
            ],
            'labels' => $labels,
        ];
    }

    #[Override]
    protected function getType(): string
    {
        return 'bar';
    }
}

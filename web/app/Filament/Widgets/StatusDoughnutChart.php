<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationOffering;
use Filament\Widgets\ChartWidget;
use Override;

class StatusDoughnutChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Distribuição por Status';

    protected ?string $maxHeight = '250px';

    protected int | string | array $columnSpan = 1;

    #[Override]
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [
                        MedicationOffering::available()->count(),
                        MedicationOffering::reserved()->count(),
                        MedicationOffering::completed()->count(),
                    ],
                    'backgroundColor' => ['#22c55e', '#eab308', '#3b82f6'],
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => ['Disponíveis', 'Reservados', 'Concluídos'],
        ];
    }

    #[Override]
    protected function getType(): string
    {
        return 'doughnut';
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}

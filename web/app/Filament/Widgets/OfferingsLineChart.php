<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationOffering;
use Filament\Widgets\ChartWidget;
use Override;

class OfferingsLineChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Ofertas - Últimos 7 dias';

    protected ?string $maxHeight = '250px';

    protected int | string | array $columnSpan = 1;

    #[Override]
    protected function getData(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[]   = MedicationOffering::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Ofertas',
                    'data'            => $data,
                    'borderColor'     => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    #[Override]
    protected function getType(): string
    {
        return 'line';
    }
}

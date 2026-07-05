<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationRequest;
use Filament\Widgets\ChartWidget;
use Override;

class RequestsLineChart extends ChartWidget
{
    #[Override]
    protected static bool $isDiscovered = false;

    #[Override]
    protected ?string $heading = 'Solicitações - Últimos 7 dias';

    #[Override]
    protected ?string $maxHeight = '250px';

    #[Override]
    protected int | string | array $columnSpan = 1;

    #[Override]
    protected function getData(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[]   = MedicationRequest::whereDate('created_at', $date->toDateString())->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Solicitações',
                    'data'            => $data,
                    'borderColor'     => '#eab308',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
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

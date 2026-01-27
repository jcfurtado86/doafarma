<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationAppointment;
use App\Models\MedicationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RequestStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    #[\Override]
    protected function getStats(): array
    {
        $pendingRequests   = MedicationRequest::pending()->count();
        $confirmedRequests = MedicationRequest::confirmed()->count();

        $upcomingAppointments = MedicationAppointment::confirmed()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->count();

        $completedAppointments = MedicationAppointment::completed()->count();

        return [
            Stat::make('Solicitações Pendentes', number_format($pendingRequests, 0, ',', '.'))
                ->description('Aguardando resposta do médico')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRequests > 10 ? 'danger' : 'warning'),

            Stat::make('Solicitações Confirmadas', number_format($confirmedRequests, 0, ',', '.'))
                ->description('Aprovadas pelos médicos')
                ->descriptionIcon('heroicon-m-check')
                ->color('success'),

            Stat::make('Agendamentos Futuros', number_format($upcomingAppointments, 0, ',', '.'))
                ->description('Entregas programadas')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Entregas Realizadas', number_format($completedAppointments, 0, ',', '.'))
                ->description('Doações finalizadas')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),
        ];
    }
}

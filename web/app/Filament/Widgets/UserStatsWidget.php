<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Usuários Pendentes', User::where('status', UserStatus::Pending)->count())
                ->description('Aguardando aprovação')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.users.index', ['tableFilters[status][value]' => 'pending'])),

            Stat::make('Médicos', User::where('role', UserRole::Doctor)->where('status', UserStatus::Approved)->count())
                ->description('Ativos na plataforma')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('info'),

            Stat::make('Receptores', User::where('role', UserRole::Receptor)->where('status', UserStatus::Approved)->count())
                ->description('Ativos na plataforma')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total de Usuários', User::where('role', '!=', UserRole::Admin)->count())
                ->description('Excluindo administradores')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }
}

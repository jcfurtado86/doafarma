<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Atividades Recentes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->placeholder('Sistema'),
                TextColumn::make('event')
                    ->label('Ação')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created'  => 'Criado',
                        'updated'  => 'Atualizado',
                        'deleted'  => 'Removido',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        default    => $state ?? 'Desconhecido',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created'  => 'success',
                        'updated'  => 'info',
                        'deleted'  => 'danger',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Entidade')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        User::class                  => 'Usuário',
                        MedicationOffering::class    => 'Oferta',
                        MedicationRequest::class     => 'Solicitação',
                        MedicationAppointment::class => 'Agendamento',
                        default                      => 'Outro',
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}

<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use Filament\Tables;
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->placeholder('Sistema'),
                Tables\Columns\TextColumn::make('event')
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
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Entidade')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        \App\Models\User::class                  => 'Usuário',
                        \App\Models\MedicationOffering::class    => 'Oferta',
                        \App\Models\MedicationRequest::class     => 'Solicitação',
                        \App\Models\MedicationAppointment::class => 'Agendamento',
                        default                                  => 'Outro',
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}

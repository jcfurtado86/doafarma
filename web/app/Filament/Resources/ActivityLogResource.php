<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Registro de Atividade';

    protected static ?string $pluralModelLabel = 'Registros de Atividades';

    protected static ?string $navigationGroup = 'Monitoramento';

    protected static ?int $navigationSort = 1;

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->placeholder('Sistema')
                    ->searchable(),
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
                        \App\Models\MedicationOffering::class    => 'Oferta de Medicamento',
                        \App\Models\MedicationRequest::class     => 'Solicitação',
                        \App\Models\MedicationAppointment::class => 'Agendamento',
                        \App\Models\Doctor::class                => 'Médico',
                        \App\Models\DoctorRating::class          => 'Avaliação',
                        default                                  => $state ?? 'N/A',
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->tooltip(fn (Activity $record): string => $record->description ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Ação')
                    ->options([
                        'created'  => 'Criado',
                        'updated'  => 'Atualizado',
                        'deleted'  => 'Removido',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Entidade')
                    ->options([
                        \App\Models\User::class                  => 'Usuário',
                        \App\Models\MedicationOffering::class    => 'Oferta de Medicamento',
                        \App\Models\MedicationRequest::class     => 'Solicitação',
                        \App\Models\MedicationAppointment::class => 'Agendamento',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    #[\Override]
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informações do Registro')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Data/Hora')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('causer.name')
                            ->label('Realizado por')
                            ->placeholder('Sistema'),
                        TextEntry::make('event')
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
                        TextEntry::make('subject_type')
                            ->label('Tipo de Entidade')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                \App\Models\User::class                  => 'Usuário',
                                \App\Models\MedicationOffering::class    => 'Oferta de Medicamento',
                                \App\Models\MedicationRequest::class     => 'Solicitação',
                                \App\Models\MedicationAppointment::class => 'Agendamento',
                                \App\Models\Doctor::class                => 'Médico',
                                \App\Models\DoctorRating::class          => 'Avaliação',
                                default                                  => $state ?? 'N/A',
                            }),
                        TextEntry::make('subject_id')
                            ->label('ID da Entidade'),
                        TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Alterações')
                    ->schema([
                        TextEntry::make('properties.old')
                            ->label('Valores Anteriores')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A')
                            ->markdown()
                            ->columnSpanFull(),
                        TextEntry::make('properties.attributes')
                            ->label('Novos Valores')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view'  => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    #[\Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[\Override]
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    #[\Override]
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

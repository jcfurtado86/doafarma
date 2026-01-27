<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Models\Doctor;
use App\Models\DoctorRating;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Registro de Atividade';

    protected static ?string $pluralModelLabel = 'Registros de Atividades';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoramento';

    protected static ?int $navigationSort = 1;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Usuário')
                    ->placeholder('Sistema')
                    ->searchable(),
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
                        MedicationOffering::class    => 'Oferta de Medicamento',
                        MedicationRequest::class     => 'Solicitação',
                        MedicationAppointment::class => 'Agendamento',
                        Doctor::class                => 'Médico',
                        DoctorRating::class          => 'Avaliação',
                        default                      => $state ?? 'N/A',
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->tooltip(fn (Activity $record): string => $record->description ?? ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('Ação')
                    ->options([
                        'created'  => 'Criado',
                        'updated'  => 'Atualizado',
                        'deleted'  => 'Removido',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Entidade')
                    ->options([
                        User::class                  => 'Usuário',
                        MedicationOffering::class    => 'Oferta de Medicamento',
                        MedicationRequest::class     => 'Solicitação',
                        MedicationAppointment::class => 'Agendamento',
                    ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')
                            ->label('De'),
                        DatePicker::make('until')
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
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informações do Registro')
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
                                User::class                  => 'Usuário',
                                MedicationOffering::class    => 'Oferta de Medicamento',
                                MedicationRequest::class     => 'Solicitação',
                                MedicationAppointment::class => 'Agendamento',
                                Doctor::class                => 'Médico',
                                DoctorRating::class          => 'Avaliação',
                                default                      => $state ?? 'N/A',
                            }),
                        TextEntry::make('subject_id')
                            ->label('ID da Entidade'),
                        TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                \Filament\Schemas\Components\Section::make('Alterações')
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

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view'  => ViewActivityLog::route('/{record}'),
        ];
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    #[Override]
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

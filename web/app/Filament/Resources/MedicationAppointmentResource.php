<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\MedicationAppointmentResource\Pages;
use App\Models\MedicationAppointment;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MedicationAppointmentResource extends Resource
{
    protected static ?string $model = MedicationAppointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $modelLabel = 'Agendamento';

    protected static ?string $pluralModelLabel = 'Agendamentos';

    protected static ?string $navigationGroup = 'Doações';

    protected static ?int $navigationSort = 3;

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('medicationRequest.receptor.name')
                    ->label('Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('medicationRequest.medicationOffering.doctor.user.name')
                    ->label('Médico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('medicationRequest.medicationOffering.drug.product_name')
                    ->label('Medicamento')
                    ->limit(20)
                    ->tooltip(fn (MedicationAppointment $record): string => $record->medicationRequest->medicationOffering->drug->product_name ?? ''),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label('Hora')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'proposed'  => 'Proposto',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'proposed'  => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\IconColumn::make('receptor_confirmed')
                    ->label('Receptor OK')
                    ->boolean(),
                Tables\Columns\IconColumn::make('doctor_confirmed')
                    ->label('Médico OK')
                    ->boolean(),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'proposed'  => 'Proposto',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                    ]),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Futuros')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('scheduled_date', '>=', now()->toDateString())),
                Tables\Filters\Filter::make('past')
                    ->label('Passados')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('scheduled_date', '<', now()->toDateString())),
                Tables\Filters\Filter::make('scheduled_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
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
                Section::make('Informações do Agendamento')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('scheduled_date')
                            ->label('Data')
                            ->date('d/m/Y'),
                        TextEntry::make('scheduled_time')
                            ->label('Hora')
                            ->time('H:i'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'proposed'  => 'Proposto',
                                'confirmed' => 'Confirmado',
                                'completed' => 'Concluído',
                                default     => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'proposed'  => 'warning',
                                'confirmed' => 'info',
                                'completed' => 'success',
                                default     => 'gray',
                            }),
                        TextEntry::make('proposed_by')
                            ->label('Proposto por')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'receptor' => 'Receptor',
                                'doctor'   => 'Médico',
                                default    => $state,
                            }),
                    ])
                    ->columns(3),
                Section::make('Confirmações')
                    ->schema([
                        TextEntry::make('receptor_confirmed')
                            ->label('Receptor Confirmou')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Sim' : 'Não')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('doctor_confirmed')
                            ->label('Médico Confirmou')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Sim' : 'Não')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    ])
                    ->columns(2),
                Section::make('Local')
                    ->schema([
                        TextEntry::make('address.location_name')
                            ->label('Nome do Local'),
                        TextEntry::make('address.full_address')
                            ->label('Endereço'),
                        TextEntry::make('address.complement')
                            ->label('Complemento'),
                        TextEntry::make('address.cep')
                            ->label('CEP'),
                    ])
                    ->columns(2),
                Section::make('Receptor')
                    ->schema([
                        TextEntry::make('medicationRequest.receptor.name')
                            ->label('Nome'),
                        TextEntry::make('medicationRequest.receptor.email')
                            ->label('E-mail'),
                        TextEntry::make('medicationRequest.receptor.phone_number')
                            ->label('Telefone'),
                    ])
                    ->columns(3),
                Section::make('Médico')
                    ->schema([
                        TextEntry::make('medicationRequest.medicationOffering.doctor.user.name')
                            ->label('Nome'),
                        TextEntry::make('medicationRequest.medicationOffering.doctor.crm')
                            ->label('CRM')
                            ->formatStateUsing(fn (MedicationAppointment $record): string => "{$record->medicationRequest->medicationOffering->doctor->crm}/{$record->medicationRequest->medicationOffering->doctor->crm_uf}"),
                        TextEntry::make('medicationRequest.medicationOffering.doctor.user.phone_number')
                            ->label('Telefone'),
                    ])
                    ->columns(3),
                Section::make('Medicamento')
                    ->schema([
                        TextEntry::make('medicationRequest.medicationOffering.drug.product_name')
                            ->label('Medicamento'),
                        TextEntry::make('medicationRequest.medicationOffering.drug.substance')
                            ->label('Princípio Ativo'),
                        TextEntry::make('medicationRequest.medicationOffering.lot_number')
                            ->label('Lote'),
                        TextEntry::make('medicationRequest.medicationOffering.quantity')
                            ->label('Quantidade'),
                    ])
                    ->columns(2),
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
            'index' => Pages\ListMedicationAppointments::route('/'),
            'view'  => Pages\ViewMedicationAppointment::route('/{record}'),
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

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::confirmed()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}

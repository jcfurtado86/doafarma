<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\MedicationAppointmentResource\Pages\ListMedicationAppointments;
use App\Filament\Resources\MedicationAppointmentResource\Pages\ViewMedicationAppointment;
use App\Models\MedicationAppointment;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class MedicationAppointmentResource extends Resource
{
    #[Override]
    protected static ?string $model = MedicationAppointment::class;

    #[Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';

    #[Override]
    protected static ?string $modelLabel = 'Agendamento';

    #[Override]
    protected static ?string $pluralModelLabel = 'Agendamentos';

    #[Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Doações';

    #[Override]
    protected static ?int $navigationSort = 3;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('medicationRequest.receptor.name')
                    ->label('Receptor')
                    ->searchable(),
                TextColumn::make('medicationRequest.medicationOffering.doctor.user.name')
                    ->label('Médico')
                    ->searchable(),
                TextColumn::make('medicationRequest.medicationOffering.drug.product_name')
                    ->label('Medicamento')
                    ->limit(20)
                    ->tooltip(fn (MedicationAppointment $record): string => $record->medicationRequest->medicationOffering->drug->product_name ?? ''),
                TextColumn::make('scheduled_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('scheduled_time')
                    ->label('Hora')
                    ->time('H:i'),
                TextColumn::make('status')
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
                IconColumn::make('receptor_confirmed')
                    ->label('Receptor OK')
                    ->boolean(),
                IconColumn::make('doctor_confirmed')
                    ->label('Médico OK')
                    ->boolean(),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'proposed'  => 'Proposto',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                    ]),
                Filter::make('upcoming')
                    ->label('Futuros')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('scheduled_date', '>=', now()->toDateString())),
                Filter::make('past')
                    ->label('Passados')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('scheduled_date', '<', now()->toDateString())),
                Filter::make('scheduled_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('De'),
                        DatePicker::make('until')
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
                \Filament\Schemas\Components\Section::make('Informações do Agendamento')
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
                \Filament\Schemas\Components\Section::make('Confirmações')
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
                \Filament\Schemas\Components\Section::make('Local')
                    ->schema([
                        TextEntry::make('address.label')
                            ->label('Nome do Local'),
                        TextEntry::make('address.street')
                            ->label('Endereço'),
                        TextEntry::make('address.complement')
                            ->label('Complemento'),
                        TextEntry::make('address.cep')
                            ->label('CEP'),
                    ])
                    ->columns(2),
                \Filament\Schemas\Components\Section::make('Receptor')
                    ->schema([
                        TextEntry::make('medicationRequest.receptor.name')
                            ->label('Nome'),
                        TextEntry::make('medicationRequest.receptor.email')
                            ->label('E-mail'),
                        TextEntry::make('medicationRequest.receptor.phone_number')
                            ->label('Telefone'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('Médico')
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
                \Filament\Schemas\Components\Section::make('Medicamento')
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

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListMedicationAppointments::route('/'),
            'view'  => ViewMedicationAppointment::route('/{record}'),
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

    public static function getNavigationBadge(): ?string
    {
        return (string) MedicationAppointment::confirmed()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}

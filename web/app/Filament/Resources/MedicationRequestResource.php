<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\MedicationRequestResource\Pages;
use App\Models\MedicationRequest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MedicationRequestResource extends Resource
{
    protected static ?string $model = MedicationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Solicitação';

    protected static ?string $pluralModelLabel = 'Solicitações';

    protected static ?string $navigationGroup = 'Doações';

    protected static ?int $navigationSort = 2;

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('receptor.name')
                    ->label('Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('medicationOffering.drug.product_name')
                    ->label('Medicamento')
                    ->limit(25)
                    ->tooltip(fn (MedicationRequest $record): string => $record->medicationOffering->drug->product_name ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('medicationOffering.doctor.user.name')
                    ->label('Médico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmada',
                        'rejected'  => 'Rejeitada',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'success',
                        'rejected'  => 'danger',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'confirmed' => 'Confirmada',
                        'rejected'  => 'Rejeitada',
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
                Section::make('Informações da Solicitação')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending'   => 'Pendente',
                                'confirmed' => 'Confirmada',
                                'rejected'  => 'Rejeitada',
                                default     => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'pending'   => 'warning',
                                'confirmed' => 'success',
                                'rejected'  => 'danger',
                                default     => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Solicitado em')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2),
                Section::make('Receptor')
                    ->schema([
                        TextEntry::make('receptor.name')
                            ->label('Nome'),
                        TextEntry::make('receptor.email')
                            ->label('E-mail'),
                        TextEntry::make('receptor.phone_number')
                            ->label('Telefone'),
                        TextEntry::make('receptor.cpf')
                            ->label('CPF'),
                    ])
                    ->columns(2),
                Section::make('Oferta de Medicamento')
                    ->schema([
                        TextEntry::make('medicationOffering.drug.product_name')
                            ->label('Medicamento'),
                        TextEntry::make('medicationOffering.drug.substance')
                            ->label('Princípio Ativo'),
                        TextEntry::make('medicationOffering.lot_number')
                            ->label('Lote'),
                        TextEntry::make('medicationOffering.expires_at')
                            ->label('Validade')
                            ->date('d/m/Y'),
                        TextEntry::make('medicationOffering.quantity')
                            ->label('Quantidade'),
                        TextEntry::make('medicationOffering.status')
                            ->label('Status da Oferta')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'available' => 'Disponível',
                                'reserved'  => 'Reservado',
                                'completed' => 'Concluído',
                                default     => $state,
                            }),
                    ])
                    ->columns(2),
                Section::make('Médico Doador')
                    ->schema([
                        TextEntry::make('medicationOffering.doctor.user.name')
                            ->label('Nome'),
                        TextEntry::make('medicationOffering.doctor.crm')
                            ->label('CRM')
                            ->formatStateUsing(fn (MedicationRequest $record): string => "{$record->medicationOffering->doctor->crm}/{$record->medicationOffering->doctor->crm_uf}"),
                        TextEntry::make('medicationOffering.doctor.user.email')
                            ->label('E-mail'),
                        TextEntry::make('medicationOffering.doctor.user.phone_number')
                            ->label('Telefone'),
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
            'index' => Pages\ListMedicationRequests::route('/'),
            'view'  => Pages\ViewMedicationRequest::route('/{record}'),
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
        return (string) static::getModel()::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::pending()->count();

        return $count > 0 ? 'warning' : 'success';
    }
}

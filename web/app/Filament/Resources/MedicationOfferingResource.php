<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\MedicationOfferingResource\Pages;
use App\Models\MedicationOffering;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MedicationOfferingResource extends Resource
{
    protected static ?string $model = MedicationOffering::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'Oferta de Medicamento';

    protected static ?string $pluralModelLabel = 'Ofertas de Medicamentos';

    protected static ?string $navigationGroup = 'Doações';

    protected static ?int $navigationSort = 1;

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('drug.product_name')
                    ->label('Medicamento')
                    ->limit(30)
                    ->tooltip(fn (MedicationOffering $record): string => $record->drug->product_name ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Médico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lot_number')
                    ->label('Lote')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (MedicationOffering $record): string => $record->expires_at->isPast() ? 'danger' : ($record->expires_at->diffInDays(now()) <= 30 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantidade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponível',
                        'reserved'  => 'Reservado',
                        'completed' => 'Concluído',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved'  => 'warning',
                        'completed' => 'info',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Disponível',
                        'reserved'  => 'Reservado',
                        'completed' => 'Concluído',
                    ]),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Vencendo em 30 dias')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('expires_at', '<=', now()->addDays(30))
                        ->where('expires_at', '>', now())),
                Tables\Filters\Filter::make('expired')
                    ->label('Vencidos')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('expires_at', '<', now())),
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
                Section::make('Informações do Medicamento')
                    ->schema([
                        TextEntry::make('drug.product_name')
                            ->label('Medicamento'),
                        TextEntry::make('drug.substance')
                            ->label('Princípio Ativo'),
                        TextEntry::make('drug.laboratory')
                            ->label('Laboratório'),
                        TextEntry::make('drug.presentation')
                            ->label('Apresentação'),
                    ])
                    ->columns(2),
                Section::make('Informações da Oferta')
                    ->schema([
                        TextEntry::make('lot_number')
                            ->label('Lote'),
                        TextEntry::make('expires_at')
                            ->label('Validade')
                            ->date('d/m/Y'),
                        TextEntry::make('quantity')
                            ->label('Quantidade'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'available' => 'Disponível',
                                'reserved'  => 'Reservado',
                                'completed' => 'Concluído',
                                default     => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'available' => 'success',
                                'reserved'  => 'warning',
                                'completed' => 'info',
                                default     => 'gray',
                            }),
                    ])
                    ->columns(2),
                Section::make('Médico Doador')
                    ->schema([
                        TextEntry::make('doctor.user.name')
                            ->label('Nome'),
                        TextEntry::make('doctor.crm')
                            ->label('CRM')
                            ->formatStateUsing(fn (MedicationOffering $record): string => "{$record->doctor->crm}/{$record->doctor->crm_uf}"),
                        TextEntry::make('doctor.user.email')
                            ->label('E-mail'),
                        TextEntry::make('doctor.user.phone_number')
                            ->label('Telefone'),
                    ])
                    ->columns(2),
                Section::make('Registro')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Cadastrado em')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsed(),
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
            'index' => Pages\ListMedicationOfferings::route('/'),
            'view'  => Pages\ViewMedicationOffering::route('/{record}'),
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
        return (string) static::getModel()::available()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}

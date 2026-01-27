<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\Drug;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopDrugsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'half';

    protected static ?string $heading = 'Medicamentos Mais Doados';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Drug::query()
                    ->whereHas('medicationOfferings')
                    ->withCount(['medicationOfferings as total_offerings'])
                    ->withSum('medicationOfferings', 'quantity')
                    ->orderByDesc('medication_offerings_sum_quantity')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Medicamento')
                    ->limit(30)
                    ->tooltip(fn (Drug $record): string => $record->product_name)
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('substance')
                    ->label('Princípio Ativo')
                    ->limit(20)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('total_offerings')
                    ->label('Ofertas')
                    ->numeric()
                    ->sortable(false)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('medication_offerings_sum_quantity')
                    ->label('Unidades')
                    ->numeric()
                    ->sortable(false),
            ])
            ->paginated(false);
    }
}

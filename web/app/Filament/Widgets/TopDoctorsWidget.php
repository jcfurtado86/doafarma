<?php

declare(strict_types = 1);

namespace App\Filament\Widgets;

use App\Models\Doctor;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopDoctorsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'half';

    protected static ?string $heading = 'Médicos Mais Ativos';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Doctor::query()
                    ->whereHas('medicationOfferings')
                    ->withCount(['medicationOfferings as total_offerings'])
                    ->withSum('medicationOfferings', 'quantity')
                    ->orderByDesc('total_offerings')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Médico')
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('crm')
                    ->label('CRM')
                    ->formatStateUsing(fn (Doctor $record): string => "{$record->crm}/{$record->crm_uf}"),
                TextColumn::make('total_offerings')
                    ->label('Ofertas')
                    ->numeric()
                    ->sortable(false)
                    ->badge()
                    ->color('success'),
                TextColumn::make('medication_offerings_sum_quantity')
                    ->label('Unidades')
                    ->numeric()
                    ->sortable(false),
            ])
            ->paginated(false);
    }
}

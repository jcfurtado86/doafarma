<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    protected static ?string $navigationGroup = 'Gerenciamento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Telefone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cpf')
                            ->label('CPF')
                            ->maxLength(11),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status e Permissões')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Tipo')
                            ->options([
                                UserRole::Doctor->value   => UserRole::Doctor->label(),
                                UserRole::Receptor->value => UserRole::Receptor->label(),
                                UserRole::Admin->value    => UserRole::Admin->label(),
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                UserStatus::Pending->value  => UserStatus::Pending->label(),
                                UserStatus::Approved->value => UserStatus::Approved->label(),
                                UserStatus::Rejected->value => UserStatus::Rejected->label(),
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informações do Sistema')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('E-mail verificado em'),
                        Forms\Components\DateTimePicker::make('status_changed_at')
                            ->label('Status alterado em')
                            ->disabled(),
                        Forms\Components\Select::make('status_changed_by')
                            ->label('Status alterado por')
                            ->relationship('statusChangedByAdmin', 'name')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin    => 'danger',
                        UserRole::Doctor   => 'info',
                        UserRole::Receptor => 'success',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::Approved => 'success',
                        UserStatus::Pending  => 'warning',
                        UserStatus::Rejected => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        UserStatus::Pending->value  => UserStatus::Pending->label(),
                        UserStatus::Approved->value => UserStatus::Approved->label(),
                        UserStatus::Rejected->value => UserStatus::Rejected->label(),
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Tipo')
                    ->options([
                        UserRole::Doctor->value   => UserRole::Doctor->label(),
                        UserRole::Receptor->value => UserRole::Receptor->label(),
                        UserRole::Admin->value    => UserRole::Admin->label(),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Usuário')
                    ->modalDescription('Tem certeza que deseja aprovar este usuário? Ele poderá acessar a plataforma.')
                    ->modalSubmitActionLabel('Sim, aprovar')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->action(function (User $record): void {
                        $record->update([
                            'status'            => UserStatus::Approved,
                            'status_changed_at' => now(),
                            'status_changed_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Usuário aprovado com sucesso!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeitar Usuário')
                    ->modalDescription('Tem certeza que deseja rejeitar este usuário? Ele não poderá acessar a plataforma.')
                    ->modalSubmitActionLabel('Sim, rejeitar')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->action(function (User $record): void {
                        $record->update([
                            'status'            => UserStatus::Rejected,
                            'status_changed_at' => now(),
                            'status_changed_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Usuário rejeitado.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Aprovar selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (User $record): void {
                                if ($record->status === UserStatus::Pending) {
                                    $record->update([
                                        'status'            => UserStatus::Approved,
                                        'status_changed_at' => now(),
                                        'status_changed_by' => Auth::id(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Usuários aprovados com sucesso!')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('reject')
                        ->label('Rejeitar selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (User $record): void {
                                if ($record->status === UserStatus::Pending) {
                                    $record->update([
                                        'status'            => UserStatus::Rejected,
                                        'status_changed_at' => now(),
                                        'status_changed_by' => Auth::id(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Usuários rejeitados.')
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', UserStatus::Pending)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('status', UserStatus::Pending)->count();

        return $count > 0 ? 'warning' : 'success';
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', '!=', UserRole::Admin); // Hide admins from regular listing
    }
}

<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * @extends Resource<User>
 */
class UserResource extends Resource
{
    #[Override]
    protected static ?string $model = User::class;

    #[Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    #[Override]
    protected static ?string $modelLabel = 'Usuário';

    #[Override]
    protected static ?string $pluralModelLabel = 'Usuários';

    #[Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Gerenciamento';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações Pessoais')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone_number')
                            ->label('Telefone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cpf')
                            ->label('CPF')
                            ->maxLength(11),
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Deixe em branco para manter a senha atual'
                                : 'Mínimo de 8 caracteres'),
                    ])
                    ->columns(2),

                Section::make('Status e Permissões')
                    ->schema([
                        Select::make('role')
                            ->label('Tipo')
                            ->options([
                                UserRole::Doctor->value   => UserRole::Doctor->label(),
                                UserRole::Receptor->value => UserRole::Receptor->label(),
                                UserRole::Admin->value    => UserRole::Admin->label(),
                            ])
                            ->required()
                            ->native(false),
                        Select::make('status')
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

                Section::make('Informações do Sistema')
                    ->schema([
                        DateTimePicker::make('email_verified_at')
                            ->label('E-mail verificado em'),
                        DateTimePicker::make('status_changed_at')
                            ->label('Status alterado em')
                            ->disabled(),
                        Select::make('status_changed_by')
                            ->label('Status alterado por')
                            ->relationship('statusChangedByAdmin', 'name')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Telefone')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin    => 'danger',
                        UserRole::Doctor   => 'info',
                        UserRole::Receptor => 'success',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::Approved => 'success',
                        UserStatus::Pending  => 'warning',
                        UserStatus::Rejected => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        UserStatus::Pending->value  => UserStatus::Pending->label(),
                        UserStatus::Approved->value => UserStatus::Approved->label(),
                        UserStatus::Rejected->value => UserStatus::Rejected->label(),
                    ]),
                SelectFilter::make('role')
                    ->label('Tipo')
                    ->options([
                        UserRole::Doctor->value   => UserRole::Doctor->label(),
                        UserRole::Receptor->value => UserRole::Receptor->label(),
                        UserRole::Admin->value    => UserRole::Admin->label(),
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Usuário')
                    ->modalDescription('Tem certeza que deseja aprovar este usuário? Ele poderá acessar a plataforma.')
                    ->modalSubmitActionLabel('Sim, aprovar')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->action(function (User $record): void {
                        $oldStatus = $record->status;

                        $record->update([
                            'status'            => UserStatus::Approved,
                            'status_changed_at' => now(),
                            'status_changed_by' => Auth::id(),
                        ]);

                        activity()
                            ->performedOn($record)
                            ->causedBy(Auth::user())
                            ->withChanges([
                                'old'        => ['status' => $oldStatus->value],
                                'attributes' => ['status' => UserStatus::Approved->value],
                            ])
                            ->event('approved')
                            ->log("Usuário {$record->name} foi aprovado");

                        Notification::make()
                            ->title('Usuário aprovado com sucesso!')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeitar Usuário')
                    ->modalDescription('Tem certeza que deseja rejeitar este usuário? Ele não poderá acessar a plataforma.')
                    ->modalSubmitActionLabel('Sim, rejeitar')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->action(function (User $record): void {
                        $oldStatus = $record->status;

                        $record->update([
                            'status'            => UserStatus::Rejected,
                            'status_changed_at' => now(),
                            'status_changed_by' => Auth::id(),
                        ]);

                        activity()
                            ->performedOn($record)
                            ->causedBy(Auth::user())
                            ->withChanges([
                                'old'        => ['status' => $oldStatus->value],
                                'attributes' => ['status' => UserStatus::Rejected->value],
                            ])
                            ->event('rejected')
                            ->log("Usuário {$record->name} foi rejeitado");

                        Notification::make()
                            ->title('Usuário rejeitado.')
                            ->warning()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Aprovar selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $count = 0;
                            $records->each(function (User $record) use (&$count): void {
                                if ($record->status === UserStatus::Pending) {
                                    $oldStatus = $record->status;
                                    $record->update([
                                        'status'            => UserStatus::Approved,
                                        'status_changed_at' => now(),
                                        'status_changed_by' => Auth::id(),
                                    ]);

                                    activity()
                                        ->performedOn($record)
                                        ->causedBy(Auth::user())
                                        ->withChanges([
                                            'old'        => ['status' => $oldStatus->value],
                                            'attributes' => ['status' => UserStatus::Approved->value],
                                        ])
                                        ->event('approved')
                                        ->log("Usuário {$record->name} foi aprovado (em lote)");

                                    $count++;
                                }
                            });

                            Notification::make()
                                ->title("$count usuário(s) aprovado(s) com sucesso!")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('reject')
                        ->label('Rejeitar selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $count = 0;
                            $records->each(function (User $record) use (&$count): void {
                                if ($record->status === UserStatus::Pending) {
                                    $oldStatus = $record->status;
                                    $record->update([
                                        'status'            => UserStatus::Rejected,
                                        'status_changed_at' => now(),
                                        'status_changed_by' => Auth::id(),
                                    ]);

                                    activity()
                                        ->performedOn($record)
                                        ->causedBy(Auth::user())
                                        ->withChanges([
                                            'old'        => ['status' => $oldStatus->value],
                                            'attributes' => ['status' => UserStatus::Rejected->value],
                                        ])
                                        ->event('rejected')
                                        ->log("Usuário {$record->name} foi rejeitado (em lote)");

                                    $count++;
                                }
                            });

                            Notification::make()
                                ->title("$count usuário(s) rejeitado(s).")
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
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
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', '!=', UserRole::Admin); // Hide admins from regular listing
    }
}

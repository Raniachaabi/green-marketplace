<?php

namespace App\Filament\Resources;

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

/**
 * Account administration — buyers and sellers, one shared table (FR-002).
 *
 * Suspending here actually does something: User::isSuspended() is checked
 * at storefront login, so this is a real kill switch, not a cosmetic flag.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Accounts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')->required(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Textarea::make('bio')->rows(3),
            Forms\Components\Textarea::make('story')->label('Producer story')->rows(3),
            Forms\Components\TextInput::make('production_method'),
            Forms\Components\Textarea::make('mission')->rows(2),
            Forms\Components\TextInput::make('founding_year')->numeric(),
            Forms\Components\Select::make('status')
                ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())
                ->required(),
            Forms\Components\Toggle::make('is_admin')->label('Admin access'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->badge()
                    ->getStateUsing(fn (User $r) => $r->roles->pluck('role')->all()),
                Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (UserStatus $state) => $state->colour()),
                Tables\Columns\TextColumn::make('created_at')->label('Joined')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
                Tables\Filters\SelectFilter::make('role')
                    ->options(['buyer' => 'Buyer', 'seller' => 'Seller'])
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('roles', fn ($q) => $q->where('role', $data['value']))
                        : $query),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $r) => $r->status === UserStatus::Active && ! $r->is_admin)
                    ->action(function (User $record) {
                        $record->forceFill(['status' => UserStatus::Suspended])->save();

                        Notification::make()->title('Account suspended')->danger()->send();
                    }),

                Tables\Actions\Action::make('reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $r) => $r->status === UserStatus::Suspended)
                    ->action(function (User $record) {
                        $record->forceFill(['status' => UserStatus::Active])->save();

                        Notification::make()->title('Account reactivated')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

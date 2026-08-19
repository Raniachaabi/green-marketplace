<?php

namespace App\Filament\Resources;

use App\Enums\CredentialStatus;
use App\Filament\Resources\CredentialResource\Pages;
use App\Models\Credential;
use App\Services\CredentialManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-022 / FR-027 — the verification queue.
 *
 * This is the screen the operator lives in. It is deliberately boring and
 * fast: see the document, see who it belongs to, approve or reject with a
 * reason. The "expiring soon" tab is the early-warning system that stops a
 * lapsed certificate from ever reaching a buyer.
 */
class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Verification';

    protected static ?string $navigationLabel = 'Credentials';

    protected static ?int $navigationSort = 1;

    /** Pending count in the sidebar — the operator's to-do list. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', CredentialStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Credential')->schema([
                Forms\Components\Select::make('credential_type_code')
                    ->relationship('credentialType', 'code')
                    ->label('Type')
                    ->disabled(),
                Forms\Components\TextInput::make('number')->label('Number')->disabled(),
                Forms\Components\TextInput::make('issuer')->label('Issuer')->disabled(),
                Forms\Components\DatePicker::make('issued_at')->disabled(),
                Forms\Components\DatePicker::make('expires_at')
                    ->helperText('Listings depending on this credential auto-suspend the day after it lapses.'),
            ])->columns(2),

            Forms\Components\Section::make('Decision')->schema([
                Forms\Components\Select::make('status')
                    ->options(collect(CredentialStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => ucfirst($c->value)])
                        ->all())
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Reason (shown to the seller)')
                    ->rows(3)
                    ->visible(fn (Forms\Get $get) => $get('status') === CredentialStatus::Rejected->value),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('credential_type_code')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('holder')
                    ->label('Holder')
                    ->getStateUsing(fn (Credential $r) => $r->holder()?->displayName() ?? '—')
                    ->searchable(query: fn (Builder $q, string $search) => $q
                        ->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('organization', fn ($o) => $o->where('legal_name', 'like', "%{$search}%"))),

                Tables\Columns\TextColumn::make('number')->label('No.')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (CredentialStatus $state) => $state->colour()),

                Tables\Columns\TextColumn::make('expires_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Credential $r) => match (true) {
                        $r->expires_at === null => 'no expiry',
                        $r->isExpired() => 'EXPIRED',
                        default => 'in '.$r->daysUntilExpiry().' days',
                    })
                    ->color(fn (Credential $r) => match (true) {
                        $r->expires_at === null => 'gray',
                        $r->isExpired() => 'danger',
                        $r->daysUntilExpiry() <= 30 => 'warning',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CredentialStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => ucfirst($c->value)])->all()),

                Tables\Filters\Filter::make('expiring_90')
                    ->label('Expiring within 90 days')
                    ->query(fn (Builder $q) => $q->expiringWithin(90)),

                Tables\Filters\Filter::make('lapsed')
                    ->label('Lapsed but still approved')
                    ->query(fn (Builder $q) => $q
                        ->where('status', CredentialStatus::Approved)
                        ->whereNotNull('expires_at')
                        ->whereDate('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Credential $r) => $r->status === CredentialStatus::Pending)
                    ->action(function (Credential $record) {
                        app(CredentialManager::class)->approve($record, auth()->user());

                        Notification::make()->title('Credential approved')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason shown to the seller')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (Credential $r) => $r->status === CredentialStatus::Pending)
                    ->action(function (Credential $record, array $data) {
                        app(CredentialManager::class)->reject($record, auth()->user(), $data['reason']);

                        Notification::make()->title('Credential rejected')->warning()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCredentials::route('/'),
            'edit' => Pages\EditCredential::route('/{record}/edit'),
        ];
    }
}

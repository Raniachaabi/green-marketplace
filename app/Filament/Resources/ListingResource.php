<?php

namespace App\Filament\Resources;

use App\Enums\ListingStatus;
use App\Filament\Resources\ListingResource\Pages;
use App\Models\AuditLog;
use App\Models\Listing;
use App\Services\Publishing\PublishingGate;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-111 — listing moderation.
 *
 * Note that the admin "publish" action calls the same PublishingGate the
 * seller-facing controller uses. There is deliberately no admin override that
 * bypasses category requirements: if an admin could publish uncertified food,
 * the verification layer would be decorative.
 */
class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Catalogue';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Listing')->schema([
                Forms\Components\KeyValue::make('title')->label('Title (by locale)')->columnSpanFull(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'slug')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->helperText('Stored in millimes. 170 TND = 170000.')
                    ->required(),
                Forms\Components\TextInput::make('unit')->required(),
                Forms\Components\TextInput::make('stock')->numeric(),
                Forms\Components\TextInput::make('lot_number')
                    ->helperText('Required for edible categories. This is what a recall runs against.'),
            ])->columns(2),

            Forms\Components\Section::make('Moderation')->schema([
                Forms\Components\Select::make('status')
                    ->options(collect(ListingStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all())
                    ->required(),
                Forms\Components\Textarea::make('status_reason')->rows(2),
            ]),

            Forms\Components\Section::make('Category attributes')->schema([
                Forms\Components\KeyValue::make('attribute_values')->columnSpanFull(),
            ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['sellerOrg', 'sellerUser']))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (Listing $r) => $r->name())
                    ->searchable(query: fn (Builder $query, string $s) => $query->where('title', 'like', "%{$s}%"))
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.slug')->label('Category')->badge()->sortable(),

                Tables\Columns\TextColumn::make('seller')
                    ->label('Sold by')
                    ->getStateUsing(fn (Listing $r) => $r->sellerLabel()),

                Tables\Columns\TextColumn::make('price')
                    ->getStateUsing(fn (Listing $r) => Money::format((int) $r->price))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ListingStatus $state) => $state->colour()),

                Tables\Columns\TextColumn::make('lot_number')->label('Lot')->toggleable()->searchable(),

                Tables\Columns\IconColumn::make('origin_verified_at')->label('Origin verified')->boolean()->toggleable(),

                Tables\Columns\TextColumn::make('published_at')->since()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ListingStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all()),
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'slug'),
            ])
            ->actions([
                // Runs the real gate. Shows every violation rather than a
                // generic failure, so the operator can tell the seller
                // precisely what to fix.
                Tables\Actions\Action::make('runGate')
                    ->label('Check & publish')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->action(function (Listing $record) {
                        $result = app(PublishingGate::class)->publish($record);

                        if ($result->fails()) {
                            Notification::make()
                                ->title('Blocked — '.count($result->messages()).' issue(s)')
                                ->body(implode("\n", $result->messages()))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Published')->success()->send();
                    }),

                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->form([Forms\Components\TextInput::make('reason')->required()])
                    ->visible(fn (Listing $r) => $r->status === ListingStatus::Active)
                    ->action(function (Listing $record, array $data) {
                        $record->forceFill([
                            'status' => ListingStatus::Suspended,
                            'status_reason' => $data['reason'],
                            'suspended_at' => now(),
                        ])->save();

                        Notification::make()->title('Listing suspended')->warning()->send();
                    }),

                // §10 — never automatic. An admin actually checks the seller's
                // registered governorate/address before this flips on, the same
                // way a credential gets approved rather than self-declared.
                Tables\Actions\Action::make('verify_origin')
                    ->label('Verify origin')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Listing $r) => ! $r->isOriginVerified() && filled($r->governorate))
                    ->action(function (Listing $record) {
                        $record->forceFill([
                            'origin_verified_at' => now(),
                            'origin_verified_by_admin_id' => auth()->id(),
                        ])->save();

                        AuditLog::record('listing.origin_verified', $record, ['governorate' => $record->governorate]);

                        Notification::make()->title('Origin verified')->success()->send();
                    }),

                Tables\Actions\Action::make('unverify_origin')
                    ->label('Remove origin verification')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Listing $r) => $r->isOriginVerified())
                    ->action(function (Listing $record) {
                        $record->forceFill(['origin_verified_at' => null, 'origin_verified_by_admin_id' => null])->save();

                        AuditLog::record('listing.origin_unverified', $record);

                        Notification::make()->title('Origin verification removed')->warning()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListings::route('/'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }
}

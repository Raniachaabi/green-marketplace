<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** FR-113 — order operations, searchable by order, buyer, seller and lot. */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Commerce';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')->disabled(),
            Forms\Components\Select::make('status')
                ->options(collect(OrderStatus::cases())
                    ->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all())
                ->required(),
            Forms\Components\Textarea::make('buyer_note')->disabled()->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['buyer', 'lines.listing.sellerUser', 'lines.listing.sellerOrg']))
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('buyer.full_name')->label('Buyer')->searchable(),
                Tables\Columns\TextColumn::make('sellers')
                    ->label('Seller(s)')
                    ->getStateUsing(fn (Order $r) => $r->lines
                        ->map(fn ($l) => $l->listing?->sellerLabel())
                        ->filter()
                        ->unique()
                        ->implode(', '))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->whereHas('lines.sellerUser', fn ($u) => $u->where('full_name', 'like', "%{$search}%"))
                        ->orWhereHas('lines.sellerOrg', fn ($o) => $o->where('legal_name', 'like', "%{$search}%"))),
                Tables\Columns\TextColumn::make('lines_count')->counts('lines')->label('Items'),
                Tables\Columns\TextColumn::make('total')
                    ->getStateUsing(fn (Order $r) => Money::format((int) $r->total))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state) => $state->colour()),
                Tables\Columns\TextColumn::make('placed_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('placed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all()),

                // Lot lookup is the first thing you need in a recall.
                Tables\Filters\Filter::make('lot')
                    ->form([Forms\Components\TextInput::make('lot_number')->label('Lot number')])
                    ->query(fn ($query, array $data) => $data['lot_number'] ?? false
                        ? $query->whereHas('lines', fn ($l) => $l->where('lot_number', $data['lot_number']))
                        : $query),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LinesRelationManager::class,
            RelationManagers\ShipmentsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

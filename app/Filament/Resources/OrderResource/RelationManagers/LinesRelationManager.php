<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderLine;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Read-only — order lines are a purchase-time snapshot, not something an admin edits by hand. */
class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Item')
                    ->getStateUsing(fn (OrderLine $r) => $r->title()),
                Tables\Columns\TextColumn::make('seller')
                    ->getStateUsing(fn (OrderLine $r) => $r->listing?->sellerLabel() ?? '—'),
                Tables\Columns\TextColumn::make('qty'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit price')
                    ->getStateUsing(fn (OrderLine $r) => Money::format((int) $r->unit_price)),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Total')
                    ->getStateUsing(fn (OrderLine $r) => Money::format((int) $r->line_total)),
                Tables\Columns\TextColumn::make('lot_number')->label('Lot')->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    protected static ?string $title = 'Shipments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('seller')
                    ->getStateUsing(fn (Shipment $r) => $r->seller()?->displayName() ?? '—'),
                Tables\Columns\TextColumn::make('carrier.name')->label('Carrier')->placeholder('Pickup'),
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ShipmentStatus $state) => $state->colour()),
                Tables\Columns\TextColumn::make('tracking_ref')->label('Tracking')->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make()->form([
                    Select::make('status')
                        ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())
                        ->required(),
                    TextInput::make('tracking_ref')->label('Tracking reference'),
                ]),
            ])
            ->bulkActions([]);
    }
}

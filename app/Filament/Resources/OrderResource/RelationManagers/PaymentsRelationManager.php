<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Read-only summary — payment decisions (confirm/mark failed) live on PaymentResource. */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->getStateUsing(fn (Payment $r) => Money::format((int) $r->amount)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->colour()),
                Tables\Columns\IconColumn::make('proof_path')->label('Proof')->boolean()
                    ->getStateUsing(fn (Payment $r) => (bool) $r->proof_path),
                Tables\Columns\TextColumn::make('paid_at')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}

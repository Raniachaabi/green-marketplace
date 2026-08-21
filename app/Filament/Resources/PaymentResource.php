<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Support\Money;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Confirming a bank transfer by hand. COD never lands here in a "needs a
 * decision" state — only `awaiting_confirmation` (transfer) does, which is
 * exactly the queue this table starts sorted by.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Commerce';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', PaymentStatus::AwaitingConfirmation)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('order_id')
                ->relationship('order', 'number')
                ->label('Order')
                ->disabled(),
            Forms\Components\TextInput::make('method')->disabled(),
            Forms\Components\TextInput::make('amount')
                ->disabled()
                ->formatStateUsing(fn ($state) => $state !== null ? Money::format((int) $state) : null),

            Forms\Components\Placeholder::make('proof_path')
                ->label('Proof of payment')
                ->content(fn (?Payment $record) => $record?->proof_path
                    ? new HtmlString(sprintf(
                        '<a href="%s" target="_blank" rel="noopener" class="fi-link text-sm font-medium text-primary-600 underline">%s</a>',
                        route('admin.payments.proof', $record),
                        'Open receipt ↗'
                    ))
                    : 'No proof uploaded.'),

            Forms\Components\Select::make('status')
                ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all())
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order.buyer']))
            ->columns([
                Tables\Columns\TextColumn::make('order.number')->label('Order')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('order.buyer.full_name')->label('Buyer')->searchable(),
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->getStateUsing(fn (Payment $r) => Money::format((int) $r->amount))
                    ->sortable(),
                Tables\Columns\IconColumn::make('proof_path')
                    ->label('Proof')
                    ->boolean()
                    ->getStateUsing(fn (Payment $r) => (bool) $r->proof_path),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PaymentStatus $state) => $state->colour()),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all()),
                Tables\Filters\SelectFilter::make('method')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => ucfirst($m->value)])->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $r) => in_array($r->status, [PaymentStatus::AwaitingConfirmation, PaymentStatus::PendingCod], true))
                    ->action(function (Payment $record) {
                        $record->forceFill(['status' => PaymentStatus::Paid, 'paid_at' => now()])->save();

                        Notification::make()->title('Payment confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $r) => $r->status !== PaymentStatus::Paid)
                    ->action(function (Payment $record) {
                        $record->forceFill(['status' => PaymentStatus::Failed])->save();

                        Notification::make()->title('Payment marked failed')->warning()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

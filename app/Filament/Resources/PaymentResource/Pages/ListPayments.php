<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getTabs(): array
    {
        return [
            'needs_confirmation' => Tab::make('Needs confirmation')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    PaymentStatus::AwaitingConfirmation, PaymentStatus::PendingCod,
                ]))
                ->badge(fn () => Payment::whereIn('status', [
                    PaymentStatus::AwaitingConfirmation, PaymentStatus::PendingCod,
                ])->count())
                ->badgeColor('warning'),

            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PaymentStatus::Paid)),

            'all' => Tab::make('All'),
        ];
    }
}

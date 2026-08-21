<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Enums\CredentialStatus;
use App\Filament\Resources\CredentialResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCredentials extends ListRecords
{
    protected static string $resource = CredentialResource::class;

    public function getTabs(): array
    {
        return [
            'queue' => Tab::make('Queue')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CredentialStatus::Pending))
                ->badge(fn () => \App\Models\Credential::where('status', CredentialStatus::Pending)->count()),

            'expiring' => Tab::make('Expiring soon')
                ->modifyQueryUsing(fn (Builder $query) => $query->expiringWithin(90))
                ->badge(fn () => \App\Models\Credential::expiringWithin(90)->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CredentialStatus::Approved)),

            'all' => Tab::make('All'),
        ];
    }
}

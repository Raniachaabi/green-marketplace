<?php

namespace App\Filament\Resources\ListingResource\Pages;

use App\Enums\ListingStatus;
use App\Filament\Resources\ListingResource;
use App\Models\Listing;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListListings extends ListRecords
{
    protected static string $resource = ListingResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Awaiting review')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ListingStatus::Pending))
                ->badge(fn () => Listing::where('status', ListingStatus::Pending)->count()),

            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ListingStatus::Suspended))
                ->badge(fn () => Listing::where('status', ListingStatus::Suspended)->count())
                ->badgeColor('danger'),

            'active' => Tab::make('Live')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ListingStatus::Active)),

            'all' => Tab::make('All'),
        ];
    }
}

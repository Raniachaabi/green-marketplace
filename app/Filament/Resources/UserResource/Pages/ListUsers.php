<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'sellers' => Tab::make('Sellers')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('role', 'seller'))),

            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', UserStatus::Suspended))
                ->badge(fn () => User::where('status', UserStatus::Suspended)->count())
                ->badgeColor('danger'),
        ];
    }
}

<?php

namespace App\Filament\Resources\GreenScoreRuleResource\Pages;

use App\Filament\Resources\GreenScoreRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGreenScoreRules extends ListRecords
{
    protected static string $resource = GreenScoreRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

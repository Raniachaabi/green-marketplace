<?php

namespace App\Filament\Resources\GreenScoreRuleResource\Pages;

use App\Filament\Resources\GreenScoreRuleResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGreenScoreRule extends CreateRecord
{
    protected static string $resource = GreenScoreRuleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $rule = static::getModel()::create($data);

        AuditLog::record('green_score_rule.created', $rule, [
            'code' => $rule->code,
            'points' => $rule->points,
            'is_active' => $rule->is_active,
        ]);

        return $rule;
    }
}

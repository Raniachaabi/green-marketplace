<?php

namespace App\Filament\Resources\GreenScoreRuleResource\Pages;

use App\Filament\Resources\GreenScoreRuleResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGreenScoreRule extends EditRecord
{
    protected static string $resource = GreenScoreRuleResource::class;

    /**
     * Audit-log every rule change (FR-Phase2 §27) — points and active/
     * inactive state directly change what buyers see as "evidence" on a
     * product's Green Score, so previous/new values are recorded here
     * before the values are lost to the update.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $before = ['points' => $record->points, 'is_active' => $record->is_active];

        $record->update($data);

        AuditLog::record('green_score_rule.updated', $record, [
            'before' => $before,
            'after' => ['points' => $record->points, 'is_active' => $record->is_active],
        ]);

        return $record;
    }
}

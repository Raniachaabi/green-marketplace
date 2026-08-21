<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * `is_admin` is deliberately not mass-assignable (User::$fillable) —
     * granting admin access is sensitive enough to keep out of ordinary
     * mass assignment, so it is force-filled here instead.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $isAdmin = Arr::pull($data, 'is_admin');

        $record->update($data);
        $record->forceFill(['is_admin' => (bool) $isAdmin])->save();

        return $record;
    }
}

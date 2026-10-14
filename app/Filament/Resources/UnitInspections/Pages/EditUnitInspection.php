<?php

namespace App\Filament\Resources\UnitInspections\Pages;

use App\Filament\Resources\UnitInspections\UnitInspectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUnitInspection extends EditRecord
{
    protected static string $resource = UnitInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
        ];
    }
}

<?php

namespace App\Filament\Resources\UnitInspections\Pages;

use App\Filament\Resources\UnitInspections\UnitInspectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitInspection extends ViewRecord
{
    protected static string $resource = UnitInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل الفحص'),
        ];
    }
}

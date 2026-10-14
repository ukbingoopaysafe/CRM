<?php

namespace App\Filament\Resources\UnitInspections\Pages;

use App\Filament\Resources\UnitInspections\UnitInspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnitInspections extends ListRecords
{
    protected static string $resource = UnitInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('تسجيل فحص ميداني جديد'),
        ];
    }
}

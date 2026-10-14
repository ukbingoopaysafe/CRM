<?php

namespace App\Filament\Resources\DealOutcomes\Pages;

use App\Filament\Resources\DealOutcomes\DealOutcomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDealOutcomes extends ListRecords
{
    protected static string $resource = DealOutcomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('تسجيل إغلاق صفقة جديدة'),
        ];
    }
}

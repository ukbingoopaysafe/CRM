<?php

namespace App\Filament\Resources\DealOutcomes\Pages;

use App\Filament\Resources\DealOutcomes\DealOutcomeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDealOutcome extends ViewRecord
{
    protected static string $resource = DealOutcomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل النتيجة'),
        ];
    }
}

<?php

namespace App\Filament\Resources\DealOutcomes\Pages;

use App\Filament\Resources\DealOutcomes\DealOutcomeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDealOutcome extends EditRecord
{
    protected static string $resource = DealOutcomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
        ];
    }
}

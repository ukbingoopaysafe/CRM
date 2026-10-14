<?php

namespace App\Filament\Resources\ClientShowings\Pages;

use App\Filament\Resources\ClientShowings\ClientShowingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClientShowing extends ViewRecord
{
    protected static string $resource = ClientShowingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل المعاينة'),
        ];
    }
}

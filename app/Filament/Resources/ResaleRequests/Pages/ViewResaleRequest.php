<?php

namespace App\Filament\Resources\ResaleRequests\Pages;

use App\Filament\Resources\ResaleRequests\ResaleRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewResaleRequest extends ViewRecord
{
    protected static string $resource = ResaleRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل الطلب'),
        ];
    }
}

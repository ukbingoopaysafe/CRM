<?php

namespace App\Filament\Resources\ResaleRequests\Pages;

use App\Filament\Resources\ResaleRequests\ResaleRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditResaleRequest extends EditRecord
{
    protected static string $resource = ResaleRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
            ForceDeleteAction::make()->label('حذف نهائي'),
            RestoreAction::make()->label('استعادة'),
        ];
    }
}

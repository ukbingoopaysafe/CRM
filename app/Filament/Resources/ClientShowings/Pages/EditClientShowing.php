<?php

namespace App\Filament\Resources\ClientShowings\Pages;

use App\Filament\Resources\ClientShowings\ClientShowingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClientShowing extends EditRecord
{
    protected static string $resource = ClientShowingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('عرض'),
            DeleteAction::make()->label('حذف'),
        ];
    }
}

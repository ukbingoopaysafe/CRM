<?php

namespace App\Filament\Resources\ClientShowings\Pages;

use App\Filament\Resources\ClientShowings\ClientShowingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientShowings extends ListRecords
{
    protected static string $resource = ClientShowingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('جدولة معاينة عميل جديدة'),
        ];
    }
}

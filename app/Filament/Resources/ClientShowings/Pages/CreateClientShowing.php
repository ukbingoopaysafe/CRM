<?php

namespace App\Filament\Resources\ClientShowings\Pages;

use App\Filament\Resources\ClientShowings\ClientShowingResource;
use App\Models\UnitNomination;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClientShowing extends CreateRecord
{
    protected static string $resource = ClientShowingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $nomination = UnitNomination::find($data['unit_nomination_id'] ?? null);

        if (!$nomination || !$nomination->isSuitableForShowing()) {
            Notification::make()
                ->title('صمام أمان المعاينات')
                ->body('لا يمكن جدولة معاينة لهذه الوحدة لأنها لم تجتز الفحص الميداني بنتيجة (مناسبة). يرجى إجراء الفحص واعتماد الوحدة أولاً.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }
}

<?php

namespace App\Filament\Resources\DealOutcomes\Pages;

use App\Filament\Resources\DealOutcomes\DealOutcomeResource;
use App\Models\Activity;
use App\Models\DealOutcome;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use Filament\Resources\Pages\CreateRecord;

class CreateDealOutcome extends CreateRecord
{
    protected static string $resource = DealOutcomeResource::class;

    protected function afterCreate(): void
    {
        /** @var DealOutcome $record */
        $record = $this->record;

        $isWon = $record->isWon();

        if ($record->request_type === 'rental' && $record->rental_request_id) {
            $req = RentalRequest::find($record->rental_request_id);
            if ($req) {
                $req->update([
                    'status' => $isWon ? RentalRequest::STATUS_CLOSED_WON : RentalRequest::STATUS_CLOSED_LOST,
                ]);

                Activity::log(
                    type: Activity::TYPE_DEAL_CLOSED,
                    description: ($isWon ? 'إغلاق ناجح لطلب الإيجار' : 'إغلاق دون تعاقد لطلب الإيجار') . ' - عمولة: ' . number_format((float) $record->commission_amount) . ' ج.م',
                    userId: $record->closed_by,
                    clientId: $req->client_id,
                    requestType: 'rental',
                    requestId: $req->id
                );
            }
        } elseif ($record->request_type === 'resale' && $record->resale_request_id) {
            $req = ResaleRequest::find($record->resale_request_id);
            if ($req) {
                $req->update([
                    'status' => $isWon ? ResaleRequest::STATUS_CLOSED_WON : ResaleRequest::STATUS_CLOSED_LOST,
                ]);

                Activity::log(
                    type: Activity::TYPE_DEAL_CLOSED,
                    description: ($isWon ? 'إغلاق ناجح لطلب الريسيل' : 'إغلاق دون تعاقد لطلب الريسيل') . ' - عمولة: ' . number_format((float) $record->commission_amount) . ' ج.م',
                    userId: $record->closed_by,
                    clientId: $req->client_id,
                    requestType: 'resale',
                    requestId: $req->id
                );
            }
        }
    }
}

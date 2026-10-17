<?php

namespace App\Filament\Resources\ResaleRequests\Pages;

use App\Filament\Resources\ResaleRequests\ResaleRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Components\Tabs;
use Filament\Components\Tabs\Tab;
use App\Filament\Resources\ResaleRequests\RelationManagers\NominationsRelationManager;
use App\Filament\Resources\ResaleRequests\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ResaleRequests\RelationManagers\FollowUpsRelationManager;

class ViewResaleRequest extends ViewRecord
{
    protected static string $resource = ResaleRequestResource::class;

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        $record = $this->getRecord();

        $clientName = $record->client ? e($record->client->name) : '-';
        $status = e($record->status ?? '-');
        $assignedAgentName = $record->assignedAgent ? e($record->assignedAgent->name) : '-';
        $brokerName = $record->broker ? e($record->broker->name) : 'بدون وسيط';
        $source = e($record->source ?? '-');

        return <<<HTML
<div class="flex flex-col space-x-2 text-sm">
    <div><span class="font-medium">العميل: </span>{$clientName}</div>
    <div><span class="font-medium">الم stage: </span>{$status}</div>
    <div><span class="font-medium">الموظف المسؤول: </span>{$assignedAgentName}</div>
    <div><span class="font-medium">الوسيط: </span>{$brokerName}</div>
    <div><span class="font-medium">المصدر: </span>{$source}</div>
</div>
HTML;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getContent(): Tabs
    {
        $record = $this->getRecord();

        // Need tab content: show a summary of requirements
        $requirements = $record->requirements ?? null;
        $needContent = '<div class="space-y-4 p-4">';
        if ($requirements) {
            $needContent .= '<div><span class="font-medium">نوع العقار: </span>'.e($requirements->property_type ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">عدد الغرف: </span>'.e($requirements->bedrooms ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">مستوى التشطيب: </span>'.e($requirements->finishing_level ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">موعد الاستلام: </span>'.e($requirements->delivery_target ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">طريقة السداد: </span>'.e($requirements->payment_method ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">العملة: </span>'.e($requirements->currency ?? '-').'</div>';
            $needContent .= '<div><span class="font-medium">المواقع المفضلة: </span>'.e($requirements->preferred_locations ?? '-').'</div>';
        } else {
            $needContent .= '<div>لا توجد متطلبات محددة.</div>';
        }
        $needContent .= '</div>';

        // Relation manager content placeholder
        $relationManagerContent = '<div class="p-4 text-center text-gray-500">لا يمكن عرض محتوى هذه العلامة حاليًا.</div>';

        return Tabs::make([
            Tab::make('الاحتياج')
                ->icon('heroicon-m-document-text')
                ->content($needContent),
            Tab::make('الترشيحات')
                ->icon('heroicon-m-user-group')
                ->content($this->getRelationManagerContent(NominationsRelationManager::class, $record)),
            Tab::make('الفحص')
                ->icon('heroicon-m-eye')
                ->content('لا توجد فحوصات'),
            Tab::make('المعاينات')
                ->icon('heroicon-m-calendar')
                ->content('لا توجد معاينات'),
            Tab::make('المتابعات')
                ->icon('heroicon-m-clock')
                ->content($this->getRelationManagerContent(FollowUpsRelationManager::class, $record)),
            Tab::make('السجل')
                ->icon('heroicon-m-photo')
                ->content($this->getRelationManagerContent(ActivitiesRelationManager::class, $record)),
        ]);
    }

    protected function getRelationManagerContent(string $relationManagerClass, $record): string
    {
        try {
            $relationManager = app($relationManagerClass, ['record' => $record]);
            $table = $relationManager->getTable(new \Filament\Tables\Table($this->getLivewireComponent()));
            return $table->getView();
        } catch (\Throwable $e) {
            return '<div class="p-4 text-center text-gray-500">خطأ في تحميل المحتوى: '.$e->getMessage().'</div>';
        }
    }
}
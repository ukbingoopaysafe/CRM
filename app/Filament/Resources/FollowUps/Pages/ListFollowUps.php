<?php

namespace App\Filament\Resources\FollowUps\Pages;

use App\Filament\Resources\FollowUps\FollowUpResource;
use App\Models\FollowUp;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFollowUps extends ListRecords
{
    protected static string $resource = FollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة تذكير / متابعة جديدة'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->badge(FollowUp::count()),

            'overdue' => Tab::make('المتأخرة')
                ->badge(FollowUp::overdue()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue()),

            'today' => Tab::make('متابعات اليوم')
                ->badge(FollowUp::today()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'upcoming' => Tab::make('القادمة')
                ->badge(FollowUp::upcoming()->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->upcoming()),

            'completed' => Tab::make('المكتملة')
                ->badge(FollowUp::where('status', FollowUp::STATUS_COMPLETED)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FollowUp::STATUS_COMPLETED)),
        ];
    }
}

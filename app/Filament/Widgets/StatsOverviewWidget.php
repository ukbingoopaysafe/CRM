<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\DealOutcome;
use App\Models\FollowUp;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalClients = Client::count();

        $activeRentals = RentalRequest::whereNotIn('status', [
            RentalRequest::STATUS_CLOSED_WON,
            RentalRequest::STATUS_CLOSED_LOST,
        ])->count();

        $activeResales = ResaleRequest::whereNotIn('status', [
            ResaleRequest::STATUS_CLOSED_WON,
            ResaleRequest::STATUS_CLOSED_LOST,
        ])->count();

        $totalCommissions = DealOutcome::where('outcome', DealOutcome::OUTCOME_WON)->sum('commission_amount');

        $overdueFollowUps = FollowUp::overdue()->count();

        return [
            Stat::make('إجمالي العملاء', $totalClients)
                ->description('قاعدة عملاء موحدة 360°')
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('primary')
                ->chart([3, 5, 8, 12, 18, 25, $totalClients]),

            Stat::make('طلبات الإيجار النشطة', $activeRentals)
                ->description('مسار الإيجارات التشغيلي')
                ->descriptionIcon(Heroicon::OutlinedKey)
                ->color('info')
                ->chart([2, 4, 3, 6, 7, 5, $activeRentals]),

            Stat::make('طلبات الريسيل النشطة', $activeResales)
                ->description('مسار صفقات الشراء والتمليك')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
                ->color('warning')
                ->chart([1, 2, 4, 3, 5, 6, $activeResales]),

            Stat::make('أرباح العمولات المحصلة', number_format((float) $totalCommissions) . ' ج.م')
                ->description('إجمالي عمولة الصفقات المغلقة بنجاح')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),

            Stat::make('المتابعات المتأخرة', $overdueFollowUps)
                ->description($overdueFollowUps > 0 ? 'تتطلب اتخاذ إجراء فوري' : 'كافة المتابعات في موعدها')
                ->descriptionIcon($overdueFollowUps > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->color($overdueFollowUps > 0 ? 'danger' : 'success'),
        ];
    }
}

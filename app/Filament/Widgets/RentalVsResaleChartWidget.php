<?php

namespace App\Filament\Widgets;

use App\Models\DealOutcome;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use Filament\Widgets\ChartWidget;

class RentalVsResaleChartWidget extends ChartWidget
{
    protected ?string $heading = 'توزيع حركة الصفقات (إيجارات مقابل ريسيل)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $rentals = RentalRequest::count();
        $resales = ResaleRequest::count();
        $dealsWon = DealOutcome::where('outcome', DealOutcome::OUTCOME_WON)->count();
        $dealsLost = DealOutcome::where('outcome', DealOutcome::OUTCOME_LOST)->count();

        return [
            'datasets' => [
                [
                    'label' => 'العدد',
                    'data' => [$rentals, $resales, $dealsWon, $dealsLost],
                    'backgroundColor' => [
                        '#0ea5e9', // Sky blue for rentals
                        '#f59e0b', // Amber for resales
                        '#10b981', // Emerald for won
                        '#ef4444', // Red for lost
                    ],
                ],
            ],
            'labels' => [
                'إجمالي طلبات الإيجار',
                'إجمالي طلبات الريسيل',
                'الصفقات المغلقة بنجاح',
                'الصفقات المغلقة دون نجاح',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

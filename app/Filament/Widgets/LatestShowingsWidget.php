<?php

namespace App\Filament\Widgets;

use App\Models\ClientShowing;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestShowingsWidget extends BaseWidget
{
    protected static bool $isLazy = false;
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('أحدث المعاينات الميدانية المجدولة للعملاء')
            ->query(ClientShowing::query()->latest('scheduled_at')->limit(6))
            ->paginated(false)
            ->columns([
                TextColumn::make('nomination.title')
                    ->label('الوحدة / المشروع')
                    ->weight('bold')
                    ->limit(35),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->default(fn (ClientShowing $record) => $record->nomination?->client?->name ?? '-')
                    ->weight('bold'),

                TextColumn::make('client.phone')
                    ->label('الهاتف')
                    ->default(fn (ClientShowing $record) => $record->nomination?->client?->phone ?? '-')
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('scheduled_at')
                    ->label('موعد وتوقيت المعاينة')
                    ->dateTime('d/m/Y - h:i A'),

                TextColumn::make('status')
                    ->label('حالة المعاينة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ClientShowing::STATUS_COMPLETED => 'success',
                        ClientShowing::STATUS_CONFIRMED, ClientShowing::STATUS_SCHEDULED => 'warning',
                        ClientShowing::STATUS_NO_SHOW, ClientShowing::STATUS_CANCELLED => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('agent.name')
                    ->label('مسؤول المرافقة'),
            ]);
    }
}

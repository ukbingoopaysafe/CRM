<?php

namespace App\Filament\Resources\ClientShowings;

use App\Filament\Resources\ClientShowings\Pages\CreateClientShowing;
use App\Filament\Resources\ClientShowings\Pages\EditClientShowing;
use App\Filament\Resources\ClientShowings\Pages\ListClientShowings;
use App\Filament\Resources\ClientShowings\Pages\ViewClientShowing;
use App\Models\ClientShowing;
use App\Models\UnitNomination;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ClientShowingResource extends Resource
{
    protected static ?string $model = ClientShowing::class;

    protected static ?string $modelLabel = 'معاينة عميل';
    protected static ?string $pluralModelLabel = 'معاينات العملاء الميدانية';
    protected static ?string $navigationLabel = 'معاينات العملاء';
    protected static string|UnitEnum|null $navigationGroup = 'الفحص والمعاينات';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل جدولة المعاينة')
                    ->description('تحديد موعد مرافقة العميل لمعاينة الوحدة مع فحص صمام أمان الجودة')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('unit_nomination_id')
                                ->label('الوحدة المرشحة للمعاينة')
                                ->options(function () {
                                    return UnitNomination::with(['rentalRequest.client', 'resaleRequest.client', 'latestInspection'])
                                        ->get()
                                        ->mapWithKeys(function (UnitNomination $item) {
                                            $clientName = $item->client?->name ?? 'عميل غير محدد';
                                            $isSuitable = $item->isSuitableForShowing();
                                            $badge = $isSuitable ? '✅ صالحة ومناسبة' : '❌ غير معتمدة (فحص مفقود أو غير مناسب)';
                                            return [$item->id => "{$item->title} - {$item->location} [العميل: {$clientName}] ({$badge})"];
                                        });
                                })
                                ->searchable()
                                ->required()
                                ->rule(function () {
                                    return function (string $attribute, $value, Closure $fail) {
                                        $nomination = UnitNomination::find($value);
                                        if (!$nomination || !$nomination->isSuitableForShowing()) {
                                            $fail('صمام أمان المعاينات: لا يمكن جدولة معاينة لهذه الوحدة لأنها لم تجتز الفحص الميداني بنتيجة (مناسبة). يرجى إجراء الفحص واعتماد الوحدة أولاً.');
                                        }
                                    };
                                })
                                ->helperText('تطبق قاعدة حماية المعاينات: الوحدات التي اجتازت الفحص الميداني بنتيجة (مناسبة) فقط هي المسموح بجدولة معاينة لها.')
                                ->columnSpanFull(),

                            DateTimePicker::make('scheduled_at')
                                ->label('موعد وتوقيت المعاينة')
                                ->default(now()->addDay()->setHour(14)->setMinute(0))
                                ->required(),

                            Select::make('assigned_to')
                                ->label('الموظف المسؤول عن مرافقة العميل')
                                ->relationship('agent', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            Select::make('status')
                                ->label('حالة المعاينة')
                                ->options([
                                    ClientShowing::STATUS_SCHEDULED => ClientShowing::STATUS_SCHEDULED,
                                    ClientShowing::STATUS_CONFIRMED => ClientShowing::STATUS_CONFIRMED,
                                    ClientShowing::STATUS_COMPLETED => ClientShowing::STATUS_COMPLETED,
                                    ClientShowing::STATUS_NO_SHOW => ClientShowing::STATUS_NO_SHOW,
                                    ClientShowing::STATUS_CANCELLED => ClientShowing::STATUS_CANCELLED,
                                    ClientShowing::STATUS_RESCHEDULED => ClientShowing::STATUS_RESCHEDULED,
                                ])
                                ->default(ClientShowing::STATUS_SCHEDULED)
                                ->required(),
                        ]),
                    ]),

                Section::make('انطباع العميل وتقرير المعاينة (Feedback)')
                    ->description('يسجل الموظف انطباع العميل بعد المعاينة وأي اعتراضات أو خطوات تالية')
                    ->schema([
                        Textarea::make('client_feedback')
                            ->label('انطباع ورد فعل العميل بعد المعاينة')
                            ->placeholder('أعجبته إطلالة الوحدة والمساحة، لكنه يريد مناقشة تخفيض السعر قليلاً')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('objections')
                            ->label('اعتراضات العميل ونقاط الخلاف (إن وجدت)')
                            ->placeholder('اعتراض على دور الوحدة، أو عدم كفاية أماكن ركن السيارات، أو طلب زيادة مدة التقسيط')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('ملاحظات المبيعات الداخلية والخطوة التالية')
                            ->placeholder('متابعة العميل غداً صباحاً لتقديم عرض رسمي بعد التواصل مع المالك')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل معاينة العميل')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('nomination.title')->label('الوحدة')->weight('bold')->columnSpan(2),
                            TextEntry::make('nomination.location')->label('الموقع'),
                            TextEntry::make('client.name')->label('اسم العميل')->weight('bold'),
                            TextEntry::make('client.phone')->label('هاتف العميل')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('status')
                                ->label('الحالة')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    ClientShowing::STATUS_COMPLETED => 'success',
                                    ClientShowing::STATUS_CONFIRMED, ClientShowing::STATUS_SCHEDULED => 'warning',
                                    ClientShowing::STATUS_NO_SHOW, ClientShowing::STATUS_CANCELLED => 'danger',
                                    default => 'info',
                                }),
                            TextEntry::make('scheduled_at')->label('الموعد')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('agent.name')->label('مسؤول المرافقة'),
                            TextEntry::make('client_feedback')
                                ->label('انطباع العميل')
                                ->placeholder('لم يُسجل بعد')
                                ->columnSpanFull(),
                            TextEntry::make('objections')
                                ->label('اعتراضات العميل')
                                ->placeholder('لا توجد اعتراضات')
                                ->columnSpanFull(),
                            TextEntry::make('notes')
                                ->label('ملاحظات والخطوة التالية')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->badge(),

                TextColumn::make('nomination.title')
                    ->label('الوحدة / المشروع')
                    ->weight('bold')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('nomination.client.name')
                    ->label('اسم العميل')
                    ->default(fn (ClientShowing $record) => $record->nomination?->client?->name ?? '-')
                    ->weight('bold'),

                TextColumn::make('nomination.client.phone')
                    ->label('هاتف العميل')
                    ->default(fn (ClientShowing $record) => $record->nomination?->client?->phone ?? '-')
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('scheduled_at')
                    ->label('موعد المعاينة')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ClientShowing::STATUS_COMPLETED => 'success',
                        ClientShowing::STATUS_CONFIRMED => 'info',
                        ClientShowing::STATUS_SCHEDULED => 'warning',
                        ClientShowing::STATUS_NO_SHOW, ClientShowing::STATUS_CANCELLED => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('agent.name')
                    ->label('المسؤول'),

                TextColumn::make('client_feedback')
                    ->label('انطباع العميل')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        ClientShowing::STATUS_SCHEDULED => ClientShowing::STATUS_SCHEDULED,
                        ClientShowing::STATUS_CONFIRMED => ClientShowing::STATUS_CONFIRMED,
                        ClientShowing::STATUS_COMPLETED => ClientShowing::STATUS_COMPLETED,
                        ClientShowing::STATUS_NO_SHOW => ClientShowing::STATUS_NO_SHOW,
                        ClientShowing::STATUS_CANCELLED => ClientShowing::STATUS_CANCELLED,
                        ClientShowing::STATUS_RESCHEDULED => ClientShowing::STATUS_RESCHEDULED,
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('مسؤول المرافقة')
                    ->relationship('agent', 'name'),
            ])
            ->recordActions([
                Action::make('recordFeedback')
                    ->label('تسجيل النتيجة')
                    ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                    ->color('success')
                    ->schema([
                        Select::make('status')
                            ->label('الحالة النهائية للمعاينة')
                            ->options([
                                ClientShowing::STATUS_COMPLETED => ClientShowing::STATUS_COMPLETED,
                                ClientShowing::STATUS_NO_SHOW => ClientShowing::STATUS_NO_SHOW,
                                ClientShowing::STATUS_CANCELLED => ClientShowing::STATUS_CANCELLED,
                                ClientShowing::STATUS_RESCHEDULED => ClientShowing::STATUS_RESCHEDULED,
                            ])
                            ->default(ClientShowing::STATUS_COMPLETED)
                            ->required(),

                        Textarea::make('client_feedback')
                            ->label('انطباع وتقييم العميل')
                            ->required(),

                        Textarea::make('objections')
                            ->label('اعتراضات العميل إن وجدت'),

                        Textarea::make('notes')
                            ->label('الخطوة التالية المحددة مع العميل'),
                    ])
                    ->action(function (ClientShowing $record, array $data): void {
                        $record->update($data);

                        Notification::make()
                            ->title('تم حفظ نتيجة المعاينة بنجاح')
                            ->success()
                            ->send();
                    }),

                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientShowings::route('/'),
            'create' => CreateClientShowing::route('/create'),
            'view' => ViewClientShowing::route('/{record}'),
            'edit' => EditClientShowing::route('/{record}/edit'),
        ];
    }
}

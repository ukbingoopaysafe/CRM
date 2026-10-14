<?php

namespace App\Filament\Resources\FollowUps;

use App\Filament\Resources\FollowUps\Pages\CreateFollowUp;
use App\Filament\Resources\FollowUps\Pages\EditFollowUp;
use App\Filament\Resources\FollowUps\Pages\ListFollowUps;
use App\Filament\Resources\FollowUps\Pages\ViewFollowUp;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class FollowUpResource extends Resource
{
    protected static ?string $model = FollowUp::class;

    protected static ?string $modelLabel = 'متابعة وتذكير';
    protected static ?string $pluralModelLabel = 'المتابعات والتذكيرات';
    protected static ?string $navigationLabel = 'المتابعات الذكية';
    protected static string|UnitEnum|null $navigationGroup = 'المتابعات والأنشطة';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل المتابعة والتذكير')
                    ->description('تحديد الإجراء المطلوب وموعد الاستحقاق والعميل المرتبط')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('action_required')
                                ->label('الإجراء والمهمة المطلوبة')
                                ->placeholder('مثال: الاتصال بالعميل لمتابعة تقرير المعاينة وتحديد موعد التعاقد')
                                ->required()
                                ->columnSpanFull(),

                            DateTimePicker::make('due_at')
                                ->label('موعد وتوقيت الاستحقاق')
                                ->default(now()->addDay()->setHour(11)->setMinute(0))
                                ->required(),

                            Select::make('assigned_to')
                                ->label('الموظف المسؤول عن المتابعة')
                                ->relationship('assignedAgent', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            Select::make('client_id')
                                ->label('العميل المستهدف')
                                ->relationship('client', 'name')
                                ->searchable(['name', 'phone'])
                                ->preload()
                                ->required(),

                            Select::make('status')
                                ->label('حالة المتابعة')
                                ->options([
                                    FollowUp::STATUS_PENDING => FollowUp::STATUS_PENDING,
                                    FollowUp::STATUS_COMPLETED => FollowUp::STATUS_COMPLETED,
                                    FollowUp::STATUS_CANCELLED => FollowUp::STATUS_CANCELLED,
                                ])
                                ->default(FollowUp::STATUS_PENDING)
                                ->required(),

                            Select::make('request_type')
                                ->label('نوع الطلب المرتبط')
                                ->options([
                                    'rental' => 'طلب إيجار',
                                    'resale' => 'طلب ريسيل وشراء',
                                    'general' => 'متابعة عامة',
                                ])
                                ->default('general')
                                ->live(),

                            Select::make('rental_request_id')
                                ->label('طلب الإيجار')
                                ->relationship('rentalRequest', 'id')
                                ->getOptionLabelFromRecordUsing(fn (RentalRequest $record) => "طلب إيجار #{$record->id} - " . ($record->client?->name ?? 'عميل'))
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('request_type') === 'rental'),

                            Select::make('resale_request_id')
                                ->label('طلب الريسيل')
                                ->relationship('resaleRequest', 'id')
                                ->getOptionLabelFromRecordUsing(fn (ResaleRequest $record) => "طلب ريسيل #{$record->id} - " . ($record->client?->name ?? 'عميل'))
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('request_type') === 'resale'),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات وتفاصيل المهمة')
                            ->placeholder('أي تفاصيل تساعد الموظف في إنجاز المتابعة بدقة')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('completion_notes')
                            ->label('تقرير ما تم تنفيذه (عند الإكمال)')
                            ->visible(fn ($get) => $get('status') === FollowUp::STATUS_COMPLETED)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل المتابعة')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('action_required')->label('الإجراء المطلوب')->weight('bold')->columnSpan(2),
                            TextEntry::make('status')
                                ->label('الحالة')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    FollowUp::STATUS_COMPLETED => 'success',
                                    FollowUp::STATUS_CANCELLED => 'danger',
                                    default => 'warning',
                                }),
                            TextEntry::make('client.name')->label('اسم العميل')->weight('bold'),
                            TextEntry::make('client.phone')->label('هاتف العميل')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('assignedAgent.name')->label('الموظف المكلف'),
                            TextEntry::make('due_at')->label('تاريخ الاستحقاق')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('completed_at')->label('تاريخ الإكمال')->dateTime('d/m/Y - h:i A')->placeholder('-'),
                            TextEntry::make('completedBy.name')->label('تم الإكمال بواسطة')->placeholder('-'),
                            TextEntry::make('notes')->label('ملاحظات المتابعة')->columnSpanFull()->placeholder('لا توجد ملاحظات'),
                            TextEntry::make('completion_notes')->label('تقرير النتيجة')->columnSpanFull()->placeholder('لم يُسجل تقرير بعد'),
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

                TextColumn::make('action_required')
                    ->label('الإجراء المطلوب')
                    ->weight('bold')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('client.phone')
                    ->label('الهاتف')
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('due_at')
                    ->label('موعد الاستحقاق')
                    ->dateTime('d/m/Y - h:i A')
                    ->color(fn (FollowUp $record): string => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn (FollowUp $record): string => $record->isOverdue() ? 'bold' : 'normal')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FollowUp::STATUS_COMPLETED => 'success',
                        FollowUp::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('assignedAgent.name')
                    ->label('المسؤول'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        FollowUp::STATUS_PENDING => FollowUp::STATUS_PENDING,
                        FollowUp::STATUS_COMPLETED => FollowUp::STATUS_COMPLETED,
                        FollowUp::STATUS_CANCELLED => FollowUp::STATUS_CANCELLED,
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('الموظف المكلف')
                    ->relationship('assignedAgent', 'name'),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('إنجاز سريع')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (FollowUp $record): bool => $record->status === FollowUp::STATUS_PENDING)
                    ->schema([
                        Textarea::make('completion_notes')
                            ->label('ماذا تم في هذه المتابعة؟')
                            ->placeholder('تم الاتصال بالعميل وأكد اهتمامه وتم التنسيق للخطوة التالية')
                            ->required(),
                    ])
                    ->action(function (FollowUp $record, array $data): void {
                        $record->markCompleted($data['completion_notes']);

                        Notification::make()
                            ->title('تم إنجاز المتابعة بنجاح')
                            ->body('تم نقل النتيجة تلقائياً إلى سجل الأنشطة والخط الزمني.')
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
            'index' => ListFollowUps::route('/'),
            'create' => CreateFollowUp::route('/create'),
            'view' => ViewFollowUp::route('/{record}'),
            'edit' => EditFollowUp::route('/{record}/edit'),
        ];
    }
}

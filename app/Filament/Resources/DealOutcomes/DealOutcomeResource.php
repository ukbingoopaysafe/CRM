<?php

namespace App\Filament\Resources\DealOutcomes;

use App\Filament\Resources\DealOutcomes\Pages\CreateDealOutcome;
use App\Filament\Resources\DealOutcomes\Pages\EditDealOutcome;
use App\Filament\Resources\DealOutcomes\Pages\ListDealOutcomes;
use App\Filament\Resources\DealOutcomes\Pages\ViewDealOutcome;
use App\Models\DealOutcome;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use App\Models\UnitNomination;
use BackedEnum;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DealOutcomeResource extends Resource
{
    protected static ?string $model = DealOutcome::class;

    protected static ?string $modelLabel = 'إغلاق صفقة وعمولة';
    protected static ?string $pluralModelLabel = 'إغلاق الصفقات والنتائج';
    protected static ?string $navigationLabel = 'إغلاق الصفقات والعمولات';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصفقات والنتائج';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الصفقة والطلب المرتبط')
                    ->description('ربط الإغلاق بطلب الإيجار أو الريسيل والوحدة المتعاقد عليها')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('request_type')
                                ->label('نوع الصفقة')
                                ->options([
                                    'rental' => 'صفقة إيجار',
                                    'resale' => 'صفقة ريسيل / تمليك',
                                ])
                                ->default('rental')
                                ->live()
                                ->required(),

                            Select::make('rental_request_id')
                                ->label('طلب الإيجار المرتبط')
                                ->relationship('rentalRequest', 'id')
                                ->getOptionLabelFromRecordUsing(fn (RentalRequest $record) => "طلب إيجار #{$record->id} - " . ($record->client?->name ?? 'عميل'))
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('request_type') === 'rental')
                                ->required(fn ($get) => $get('request_type') === 'rental'),

                            Select::make('resale_request_id')
                                ->label('طلب الريسيل المرتبط')
                                ->relationship('resaleRequest', 'id')
                                ->getOptionLabelFromRecordUsing(fn (ResaleRequest $record) => "طلب ريسيل #{$record->id} - " . ($record->client?->name ?? 'عميل'))
                                ->searchable()
                                ->preload()
                                ->visible(fn ($get) => $get('request_type') === 'resale')
                                ->required(fn ($get) => $get('request_type') === 'resale'),

                            Select::make('unit_nomination_id')
                                ->label('الوحدة المتعاقد عليها (اختياري)')
                                ->relationship('nomination', 'title')
                                ->getOptionLabelFromRecordUsing(fn (UnitNomination $record) => "{$record->title} ({$record->location})")
                                ->searchable()
                                ->preload(),

                            Select::make('broker_id')
                                ->label('الوسيط الشريك المشارك بالصفقة')
                                ->relationship('broker', 'name')
                                ->searchable()
                                ->placeholder('بدون وسيط خارجي'),

                            Select::make('closed_by')
                                ->label('مسؤول الإغلاق (Closer)')
                                ->relationship('closer', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            DateTimePicker::make('closed_at')
                                ->label('تاريخ وتوقيت الإغلاق')
                                ->default(now())
                                ->required(),

                            Select::make('outcome')
                                ->label('نتيجة الصفقة')
                                ->options([
                                    DealOutcome::OUTCOME_WON => 'صفقة ناجحة (Won - تم التعاقد)',
                                    DealOutcome::OUTCOME_LOST => 'صفقة خاسرة (Lost - دون اتفاق)',
                                ])
                                ->default(DealOutcome::OUTCOME_WON)
                                ->live()
                                ->required(),
                        ]),
                    ]),

                Section::make('البيانات المالية والأرباح والعمولات')
                    ->description('توثيق المبالغ المحصلة وعمولة الشركة وحصة الوسيط الشريك')
                    ->visible(fn ($get) => $get('outcome') === DealOutcome::OUTCOME_WON)
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('final_price')
                                ->label('القيمة المالية النهائية للصفقة')
                                ->numeric()
                                ->prefix('EGP')
                                ->required(fn ($get) => $get('outcome') === DealOutcome::OUTCOME_WON)
                                ->helperText('قيمة الإيجار السنوي/الشهري، أو سعر بيع الوحدة الإجمالي'),

                            TextInput::make('commission_amount')
                                ->label('إجمالي عمولة الشركة المحصلة')
                                ->numeric()
                                ->prefix('EGP')
                                ->required(fn ($get) => $get('outcome') === DealOutcome::OUTCOME_WON),

                            TextInput::make('broker_commission')
                                ->label('عمولة الوسيط الشريك (إن وجد)')
                                ->numeric()
                                ->prefix('EGP')
                                ->default(0),
                        ]),
                    ]),

                Section::make('أسباب عدم إتمام الصفقة (Loss Reasons)')
                    ->description('تحليل وتوثيق أسباب الخسارة لتحسين معدلات التحويل مستقبلاً')
                    ->visible(fn ($get) => $get('outcome') === DealOutcome::OUTCOME_LOST)
                    ->schema([
                        Select::make('loss_reason')
                            ->label('السبب الرئيسي للخسارة')
                            ->options([
                                'عجز مالي أو عدم توفر السيولة' => 'عجز مالي أو عدم توفر السيولة',
                                'شراء/استئجار وحدة أخرى من وسيط منافس' => 'شراء/استئجار وحدة أخرى من وسيط منافس',
                                'تراجع العميل عن فكرة الانتقال/الشراء' => 'تراجع العميل عن فكرة الانتقال/الشراء',
                                'اختلاف في شروط العقد مع المالك' => 'اختلاف في شروط العقد مع المالك',
                                'عدم توفر متطلبات العميل بالميزانية المحددة' => 'عدم توفر متطلبات العميل بالميزانية المحددة',
                                'أخرى' => 'أسباب أخرى',
                            ])
                            ->required(fn ($get) => $get('outcome') === DealOutcome::OUTCOME_LOST),
                    ]),

                Section::make('ملاحظات ختامية وتوثيق')
                    ->schema([
                        Textarea::make('notes')
                            ->label('تفاصيل وملاحظات إضافية حول الصفقة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('ملخص إغلاق الصفقة')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('outcome')
                                ->label('النتيجة')
                                ->badge()
                                ->color(fn (string $state): string => $state === DealOutcome::OUTCOME_WON ? 'success' : 'danger'),
                            TextEntry::make('request_type')
                                ->label('نوع الصفقة')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state === 'rental' ? 'إيجار' : 'ريسيل'),
                            TextEntry::make('client.name')->label('اسم العميل')->weight('bold'),
                            TextEntry::make('nomination.title')->label('الوحدة التعاقدية')->default('-'),
                            TextEntry::make('broker.name')->label('الوسيط الشريك')->default('بدون وسيط'),
                            TextEntry::make('closer.name')->label('مسؤول الإغلاق'),
                            TextEntry::make('closed_at')->label('تاريخ الإغلاق')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('final_price')
                                ->label('القيمة المالية')
                                ->money('EGP')
                                ->weight('bold')
                                ->visible(fn ($record) => $record->outcome === DealOutcome::OUTCOME_WON),
                            TextEntry::make('commission_amount')
                                ->label('عمولة الشركة')
                                ->money('EGP')
                                ->color('success')
                                ->weight('bold')
                                ->visible(fn ($record) => $record->outcome === DealOutcome::OUTCOME_WON),
                            TextEntry::make('broker_commission')
                                ->label('عمولة الوسيط')
                                ->money('EGP')
                                ->visible(fn ($record) => $record->outcome === DealOutcome::OUTCOME_WON),
                            TextEntry::make('loss_reason')
                                ->label('سبب الخسارة')
                                ->color('danger')
                                ->weight('bold')
                                ->visible(fn ($record) => $record->outcome === DealOutcome::OUTCOME_LOST)
                                ->columnSpanFull(),
                            TextEntry::make('notes')->label('ملاحظات')->columnSpanFull(),
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

                TextColumn::make('outcome')
                    ->label('النتيجة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DealOutcome::OUTCOME_WON => 'success',
                        DealOutcome::OUTCOME_LOST => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('request_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'rental' ? 'إيجار' : 'ريسيل')
                    ->color('info'),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->default(fn (DealOutcome $record) => $record->client?->name ?? '-')
                    ->weight('bold'),

                TextColumn::make('final_price')
                    ->label('قيمة الصفقة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('commission_amount')
                    ->label('عمولة الشركة')
                    ->money('EGP')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('broker_commission')
                    ->label('عمولة الوسيط')
                    ->money('EGP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('closer.name')
                    ->label('المسؤول'),

                TextColumn::make('closed_at')
                    ->label('تاريخ الإغلاق')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('outcome')
                    ->label('النتيجة')
                    ->options([
                        DealOutcome::OUTCOME_WON => 'ناجحة (Won)',
                        DealOutcome::OUTCOME_LOST => 'خاسرة (Lost)',
                    ]),

                SelectFilter::make('request_type')
                    ->label('نوع الصفقة')
                    ->options([
                        'rental' => 'إيجار',
                        'resale' => 'ريسيل',
                    ]),

                SelectFilter::make('closed_by')
                    ->label('المسؤول')
                    ->relationship('closer', 'name'),
            ])
            ->recordActions([
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
            'index' => ListDealOutcomes::route('/'),
            'create' => CreateDealOutcome::route('/create'),
            'view' => ViewDealOutcome::route('/{record}'),
            'edit' => EditDealOutcome::route('/{record}/edit'),
        ];
    }
}

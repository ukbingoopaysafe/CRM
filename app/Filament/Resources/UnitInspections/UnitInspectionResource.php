<?php

namespace App\Filament\Resources\UnitInspections;

use App\Filament\Resources\UnitInspections\Pages\CreateUnitInspection;
use App\Filament\Resources\UnitInspections\Pages\EditUnitInspection;
use App\Filament\Resources\UnitInspections\Pages\ListUnitInspections;
use App\Filament\Resources\UnitInspections\Pages\ViewUnitInspection;
use App\Models\UnitInspection;
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
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UnitInspectionResource extends Resource
{
    protected static ?string $model = UnitInspection::class;

    protected static ?string $modelLabel = 'فحص ميداني';
    protected static ?string $pluralModelLabel = 'فحوصات الوحدات الميدانية';
    protected static ?string $navigationLabel = 'فحص الوحدات';
    protected static string|UnitEnum|null $navigationGroup = 'الفحص والمعاينات';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الفحص الميداني')
                    ->description('توثيق فحص الوحدة وجودتها ومطابقتها للمواصفات قبل عرضها على العميل')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('unit_nomination_id')
                                ->label('الوحدة أو المشروع المرشح')
                                ->relationship('nomination', 'title')
                                ->getOptionLabelFromRecordUsing(fn (UnitNomination $record) => "{$record->title} - {$record->location} " . ($record->unit_code ? "[{$record->unit_code}]" : ''))
                                ->searchable(['title', 'location', 'unit_code'])
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Select::make('inspected_by')
                                ->label('المسؤول عن الفحص')
                                ->relationship('inspector', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            DateTimePicker::make('inspected_at')
                                ->label('تاريخ ووقت الفحص')
                                ->default(now())
                                ->required(),

                            Select::make('result')
                                ->label('نتيجة الفحص (صمام الأمان)')
                                ->options([
                                    UnitInspection::RESULT_SUITABLE => 'مناسبة للمعاينة (تسمح بجدولة موعد للعميل)',
                                    UnitInspection::RESULT_UNSUITABLE => 'غير مناسبة (تمنع جدولة أي معاينة للعميل)',
                                ])
                                ->default(UnitInspection::RESULT_SUITABLE)
                                ->live()
                                ->required(),

                            Select::make('condition_rating')
                                ->label('تقييم حالة الوحدة والتشطيب')
                                ->options([
                                    'ممتاز' => 'ممتاز (جاهز للسكن الفوري)',
                                    'جيد جداً' => 'جيد جداً (حالة ممتازة مع ملاحظات شكلية بسيطة)',
                                    'جيد' => 'جيد (مقبول ويحتاج مراجعة طفيفة)',
                                    'يحتاج صيانة' => 'يحتاج صيانة (سباكة/كهرباء/دهانات)',
                                    'سيئ' => 'سيئ / غير مؤهل للعملاء',
                                ])
                                ->default('ممتاز')
                                ->required(),

                            Toggle::make('is_matched_reality')
                                ->label('مطابقة للصور والمعلومات المعلنة؟')
                                ->default(true)
                                ->columnSpanFull(),
                        ]),

                        Textarea::make('rejection_reason')
                            ->label('سبب الرفض وعدم الصلاحية (إلزامي في حال كانت الوحدة غير مناسبة)')
                            ->placeholder('وضح بالتفصيل: وجود رطوبة شديدة، إطلالة سيئة غير مطابقة، فرش متهالك، خلاف مع المالك...')
                            ->visible(fn ($get) => $get('result') === UnitInspection::RESULT_UNSUITABLE)
                            ->required(fn ($get) => $get('result') === UnitInspection::RESULT_UNSUITABLE)
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('تقرير وملاحظات المفتش الميداني')
                            ->placeholder('سجل انطباعك عن الجيران، مدخل العمارة، المصعد، موقف السيارات، ومستوى الصيانة العام')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تقرير الفحص الميداني للوحدة')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('nomination.title')->label('الوحدة المرشحة')->weight('bold')->columnSpan(2),
                            TextEntry::make('nomination.location')->label('الموقع'),
                            TextEntry::make('result')
                                ->label('نتيجة الفحص')
                                ->badge()
                                ->color(fn (string $state): string => $state === UnitInspection::RESULT_SUITABLE ? 'success' : 'danger'),
                            TextEntry::make('condition_rating')->label('تقييم الحالة')->badge(),
                            IconEntry::make('is_matched_reality')->label('مطابقة للواقع')->boolean(),
                            TextEntry::make('inspector.name')->label('المسؤول عن الفحص'),
                            TextEntry::make('inspected_at')->label('تاريخ الفحص')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('rejection_reason')
                                ->label('أسباب عدم الصلاحية')
                                ->color('danger')
                                ->weight('bold')
                                ->visible(fn ($record) => $record->result === UnitInspection::RESULT_UNSUITABLE)
                                ->columnSpanFull(),
                            TextEntry::make('notes')
                                ->label('ملاحظات الفحص التفصيلية')
                                ->default('لا توجد ملاحظات إضافية')
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
                    ->searchable()
                    ->limit(35),

                TextColumn::make('nomination.location')
                    ->label('الموقع')
                    ->searchable(),

                TextColumn::make('result')
                    ->label('النتيجة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        UnitInspection::RESULT_SUITABLE => 'success',
                        UnitInspection::RESULT_UNSUITABLE => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('condition_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_matched_reality')
                    ->label('مطابقة للواقع')
                    ->boolean(),

                TextColumn::make('inspector.name')
                    ->label('مفتش الجودة'),

                TextColumn::make('inspected_at')
                    ->label('تاريخ الفحص')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('result')
                    ->label('نتيجة الفحص')
                    ->options([
                        UnitInspection::RESULT_SUITABLE => 'مناسبة للمعاينة',
                        UnitInspection::RESULT_UNSUITABLE => 'غير مناسبة',
                    ]),

                SelectFilter::make('condition_rating')
                    ->label('حالة الوحدة')
                    ->options([
                        'ممتاز' => 'ممتاز',
                        'جيد جداً' => 'جيد جداً',
                        'جيد' => 'جيد',
                        'يحتاج صيانة' => 'يحتاج صيانة',
                        'سيئ' => 'سيئ',
                    ]),

                SelectFilter::make('inspected_by')
                    ->label('المفتش')
                    ->relationship('inspector', 'name'),
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
            'index' => ListUnitInspections::route('/'),
            'create' => CreateUnitInspection::route('/create'),
            'view' => ViewUnitInspection::route('/{record}'),
            'edit' => EditUnitInspection::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ResaleRequests\RelationManagers;

use App\Models\UnitInspection;
use App\Models\UnitNomination;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NominationsRelationManager extends RelationManager
{
    protected static string $relationship = 'nominations';

    protected static ?string $title = 'ترشيحات المشروعات والوحدات لهذا الطلب';
    protected static ?string $modelLabel = 'ترشيح وحدة ريسيل';
    protected static ?string $pluralModelLabel = 'ترشيحات الوحدات والمشروعات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('title')
                        ->label('عنوان / وصف الوحدة أو المشروع')
                        ->placeholder('مثال: تاون هاوس 260م بكمباوند بادية بالم هيلز')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('unit_code')
                        ->label('كود الوحدة / المشروع الداخلي')
                        ->placeholder('مثال: BADYA-TH-12'),

                    TextInput::make('location')
                        ->label('المنطقة / الكمباوند')
                        ->placeholder('مثال: 6 أكتوبر - بادية')
                        ->required(),

                    Select::make('property_type')
                        ->label('نوع العقار')
                        ->options([
                            'شقة' => 'شقة',
                            'فيلا مستقلة' => 'فيلا مستقلة',
                            'تاون هاوس' => 'تاون هاوس',
                            'توين هاوس' => 'توين هاوس',
                            'دوبلكس' => 'دوبلكس',
                            'بنتهاوس' => 'بنتهاوس',
                            'تجاري' => 'تجاري',
                            'إداري' => 'إداري',
                        ])
                        ->default('شقة')
                        ->required(),

                    TextInput::make('bedrooms')
                        ->label('عدد الغرف')
                        ->numeric()
                        ->minValue(1)
                        ->default(3),

                    TextInput::make('price')
                        ->label('السعر الإجمالي المطلوب')
                        ->numeric()
                        ->required()
                        ->prefix('EGP'),

                    TextInput::make('media_url')
                        ->label('رابط الصور / العرض التقديمي (Brochure / Drive)')
                        ->placeholder('https://drive.google.com/...')
                        ->url()
                        ->columnSpan(2),

                    Select::make('client_reaction')
                        ->label('استجابة وتقييم العميل للترشيح')
                        ->options([
                            UnitNomination::REACTION_PENDING => UnitNomination::REACTION_PENDING,
                            UnitNomination::REACTION_INTERESTED => UnitNomination::REACTION_INTERESTED,
                            UnitNomination::REACTION_REJECTED => UnitNomination::REACTION_REJECTED,
                        ])
                        ->default(UnitNomination::REACTION_PENDING)
                        ->required(),

                    Hidden::make('request_type')
                        ->default(UnitNomination::TYPE_RESALE),

                    Hidden::make('created_by')
                        ->default(fn () => auth()->id()),
                ]),

                Textarea::make('reaction_notes')
                    ->label('ملاحظات واستجابة العميل المالية والمكانية')
                    ->placeholder('سجل انطباع العميل حول السعر، أنظمة السداد، أو رغبته في زيارة الموقع')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')->label('الوصف')->weight('bold'),
                TextEntry::make('unit_code')->label('كود الوحدة')->badge(),
                TextEntry::make('location')->label('الموقع'),
                TextEntry::make('price')->label('السعر الإجمالي')->money('EGP'),
                TextEntry::make('client_reaction')->label('استجابة العميل')->badge(),
                TextEntry::make('media_url')->label('رابط التفاصيل')->url(fn ($record) => $record->media_url),
                TextEntry::make('reaction_notes')->label('ملاحظات العميل')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('unit_code')
                    ->label('الكود')
                    ->badge()
                    ->default('-'),

                TextColumn::make('title')
                    ->label('عنوان الوحدة / المشروع')
                    ->weight('bold')
                    ->limit(30),

                TextColumn::make('location')
                    ->label('الموقع'),

                TextColumn::make('price')
                    ->label('السعر الإجمالي')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('client_reaction')
                    ->label('استجابة العميل')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        UnitNomination::REACTION_INTERESTED => 'success',
                        UnitNomination::REACTION_REJECTED => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('inspection_status')
                    ->label('صلاحية المعاينة')
                    ->badge()
                    ->state(function (UnitNomination $record): string {
                        if ($record->isSuitableForShowing()) {
                            return 'جاهزة للمعاينة (فحص مناسب)';
                        }
                        $latest = $record->latestInspection;
                        if ($latest && $latest->result === UnitInspection::RESULT_UNSUITABLE) {
                            return 'غير مناسبة (مرفوضة بالفحص)';
                        }
                        return 'بانتظار الفحص الميداني';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'جاهزة للمعاينة (فحص مناسب)' => 'success',
                        'غير مناسبة (مرفوضة بالفحص)' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('media_url')
                    ->label('الملفات')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->url(fn (UnitNomination $record): ?string => $record->media_url, shouldOpenInNewTab: true)
                    ->formatStateUsing(fn ($state) => $state ? 'فتح الرابط' : '-'),

                TextColumn::make('created_at')
                    ->label('تاريخ الترشيح')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label('ترشيح وحدة أو مشروع جديد'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

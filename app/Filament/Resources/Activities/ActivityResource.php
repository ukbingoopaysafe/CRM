<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\CreateActivity;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Models\Activity;
use App\Models\Client;
use App\Models\RentalRequest;
use App\Models\ResaleRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $modelLabel = 'نشاط وتواصل';
    protected static ?string $pluralModelLabel = 'سجل الأنشطة والاتصالات';
    protected static ?string $navigationLabel = 'سجل الأنشطة';
    protected static string|UnitEnum|null $navigationGroup = 'المتابعات والأنشطة';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تسجيل نشاط أو تفاعل مع العميل')
                    ->description('توثيق كافة المكالمات والرسائل والاجتماعات في الخط الزمني')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('type')
                                ->label('نوع النشاط')
                                ->options([
                                    Activity::TYPE_CALL => Activity::TYPE_CALL,
                                    Activity::TYPE_WHATSAPP => Activity::TYPE_WHATSAPP,
                                    Activity::TYPE_MEETING => Activity::TYPE_MEETING,
                                    Activity::TYPE_SHOWING => Activity::TYPE_SHOWING,
                                    Activity::TYPE_INSPECTION => Activity::TYPE_INSPECTION,
                                    Activity::TYPE_NOMINATION => Activity::TYPE_NOMINATION,
                                    Activity::TYPE_STATUS_CHANGE => Activity::TYPE_STATUS_CHANGE,
                                    Activity::TYPE_DEAL_CLOSED => Activity::TYPE_DEAL_CLOSED,
                                    Activity::TYPE_NOTE => Activity::TYPE_NOTE,
                                ])
                                ->default(Activity::TYPE_CALL)
                                ->required(),

                            Select::make('client_id')
                                ->label('العميل')
                                ->relationship('client', 'name')
                                ->searchable(['name', 'phone'])
                                ->preload()
                                ->required(),

                            Select::make('user_id')
                                ->label('الموظف المسؤول')
                                ->relationship('user', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            DateTimePicker::make('occurred_at')
                                ->label('توقيت الحدوث')
                                ->default(now())
                                ->required(),

                            TextInput::make('result')
                                ->label('نتيجة التواصل')
                                ->placeholder('مثال: مهتم، طلب فرصة بديلة، تأكيد الموعد'),

                            Select::make('request_type')
                                ->label('نوع الطلب المرتبط')
                                ->options([
                                    'rental' => 'طلب إيجار',
                                    'resale' => 'طلب ريسيل',
                                    'general' => 'تواصل عام',
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

                        Textarea::make('description')
                            ->label('تفاصيل ما دار خلال التواصل والمخرجات')
                            ->placeholder('اكتب تفاصيل المكالمة أو الاجتماع بدقة لمساعدة باقي أعضاء الفريق')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل النشاط')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('type')->label('نوع النشاط')->badge(),
                            TextEntry::make('client.name')->label('العميل')->weight('bold'),
                            TextEntry::make('client.phone')->label('الهاتف')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('user.name')->label('الموظف المسجل'),
                            TextEntry::make('occurred_at')->label('الوقت والتاريخ')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('result')->label('النتيجة')->badge()->color('gray')->default('-'),
                            TextEntry::make('description')->label('تفاصيل النشاط')->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->badge(),

                TextColumn::make('type')
                    ->label('نوع النشاط')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Activity::TYPE_CALL => 'info',
                        Activity::TYPE_WHATSAPP => 'success',
                        Activity::TYPE_MEETING, Activity::TYPE_SHOWING => 'warning',
                        Activity::TYPE_DEAL_CLOSED => 'primary',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('العميل')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('client.phone')
                    ->label('الهاتف')
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('description')
                    ->label('تفاصيل النشاط')
                    ->limit(55)
                    ->wrap(),

                TextColumn::make('result')
                    ->label('النتيجة')
                    ->badge()
                    ->color('gray')
                    ->default('-'),

                TextColumn::make('user.name')
                    ->label('المسجل'),

                TextColumn::make('occurred_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع النشاط')
                    ->options([
                        Activity::TYPE_CALL => Activity::TYPE_CALL,
                        Activity::TYPE_WHATSAPP => Activity::TYPE_WHATSAPP,
                        Activity::TYPE_MEETING => Activity::TYPE_MEETING,
                        Activity::TYPE_SHOWING => Activity::TYPE_SHOWING,
                        Activity::TYPE_DEAL_CLOSED => Activity::TYPE_DEAL_CLOSED,
                    ]),

                SelectFilter::make('user_id')
                    ->label('الموظف')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
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
            'index' => ListActivities::route('/'),
            'create' => CreateActivity::route('/create'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}

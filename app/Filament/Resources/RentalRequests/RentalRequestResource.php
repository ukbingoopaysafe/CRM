<?php

namespace App\Filament\Resources\RentalRequests;

use App\Filament\Resources\RentalRequests\Pages\CreateRentalRequest;
use App\Filament\Resources\RentalRequests\Pages\EditRentalRequest;
use App\Filament\Resources\RentalRequests\Pages\ListRentalRequests;
use App\Filament\Resources\RentalRequests\Pages\ViewRentalRequest;
use App\Filament\Resources\RentalRequests\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\RentalRequests\RelationManagers\FollowUpsRelationManager;
use App\Filament\Resources\RentalRequests\RelationManagers\NominationsRelationManager;
use App\Models\Client;
use App\Models\RentalRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RentalRequestResource extends Resource
{
    protected static ?string $model = RentalRequest::class;

    protected static ?string $modelLabel = 'طلب إيجار';
    protected static ?string $pluralModelLabel = 'طلبات الإيجار';
    protected static ?string $navigationLabel = 'طلبات الإيجار';
    protected static string|UnitEnum|null $navigationGroup = 'قسم الإيجارات';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isRentalAgent() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الطلب والعميل')
                    ->description('ربط الطلب بعميل قائم أو وسيط وتحديد الموظف المسؤول والحالة التشغيلية')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('client_id')
                                ->label('العميل')
                                ->relationship('client', 'name')
                                ->searchable(['name', 'phone'])
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->label('اسم العميل')->required(),
                                    TextInput::make('phone')->label('رقم الهاتف')->tel()->required(),
                                    TextInput::make('email')->label('البريد الإلكتروني')->email(),
                                    TextInput::make('nationality')->label('الجنسية')->default('مصري'),
                                ])
                                ->helperText('يمكنك البحث بالاسم أو رقم الهاتف، أو إنشاء عميل جديد مباشرة'),

                            Select::make('broker_id')
                                ->label('الوسيط الشريك (اختياري)')
                                ->relationship('broker', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('بدون وسيط (عميل مباشر)'),

                            Select::make('assigned_to')
                                ->label('الموظف المسؤول')
                                ->relationship('assignedAgent', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            Select::make('source')
                                ->label('مصدر الطلب')
                                ->options([
                                    'واتساب الشركة' => 'واتساب الشركة',
                                    'إعلان ممول فيسبوك' => 'إعلان ممول فيسبوك',
                                    'موقع الشركة' => 'موقع الشركة',
                                    'ترشيح صديق' => 'ترشيح صديق',
                                    'وسيط شريك' => 'وسيط شريك',
                                    'زيارة للمقر' => 'زيارة للمقر',
                                    'أخرى' => 'أخرى',
                                ])
                                ->default('واتساب الشركة')
                                ->required(),

                            Select::make('status')
                                ->label('الحالة التشغيلية')
                                ->options([
                                    RentalRequest::STATUS_NEW => RentalRequest::STATUS_NEW,
                                    RentalRequest::STATUS_ACTIVE => RentalRequest::STATUS_ACTIVE,
                                    RentalRequest::STATUS_CONTACTED => RentalRequest::STATUS_CONTACTED,
                                    RentalRequest::STATUS_SEARCHING => RentalRequest::STATUS_SEARCHING,
                                    RentalRequest::STATUS_OPTIONS_SENT => RentalRequest::STATUS_OPTIONS_SENT,
                                    RentalRequest::STATUS_SHOWING_SCHEDULED => RentalRequest::STATUS_SHOWING_SCHEDULED,
                                    RentalRequest::STATUS_NEGOTIATING => RentalRequest::STATUS_NEGOTIATING,
                                    RentalRequest::STATUS_ON_HOLD => RentalRequest::STATUS_ON_HOLD,
                                    RentalRequest::STATUS_CLOSED_WON => RentalRequest::STATUS_CLOSED_WON,
                                    RentalRequest::STATUS_CLOSED_LOST => RentalRequest::STATUS_CLOSED_LOST,
                                ])
                                ->default(RentalRequest::STATUS_NEW)
                                ->required(),

                            TextInput::make('family_status')
                                ->label('حالة الأسرة وعدد الأفراد')
                                ->placeholder('مثال: أسرة 4 أفراد، فردي، عرسان جدد'),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات عامة حول الطلب')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('متطلبات ومواصفات السكن المطلوبة')
                    ->description('تحديد نوع العقار وتفضيلات الموقع والتأثيث وتاريخ الانتقال')
                    ->relationship('requirements')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('property_type')
                                ->label('نوع العقار')
                                ->options([
                                    'شقة' => 'شقة',
                                    'دوبلكس' => 'دوبلكس',
                                    'بنتهاوس' => 'بنتهاوس',
                                    'فيلا مستقلة' => 'فيلا مستقلة',
                                    'تاون هاوس' => 'تاون هاوس',
                                    'توين هاوس' => 'توين هاوس',
                                    'ستوديو' => 'ستوديو',
                                    'مقر إداري' => 'مقر إداري',
                                ])
                                ->default('شقة')
                                ->required(),

                            TextInput::make('bedrooms')
                                ->label('عدد الغرف المطلوبة')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('مثال: 3'),

                            Select::make('furnishing_status')
                                ->label('حالة التأثيث')
                                ->options([
                                    'مفروش بالكامل' => 'مفروش بالكامل',
                                    'غير مفروش' => 'غير مفروش',
                                    'أجهزة ومطبخ فقط' => 'أجهزة ومطبخ فقط',
                                ])
                                ->default('مفروش بالكامل')
                                ->required(),

                            TextInput::make('rental_duration')
                                ->label('مدة الإيجار المستهدفة')
                                ->default('سنة قابلة للتجديد')
                                ->placeholder('مثال: سنة، 6 أشهر'),

                            DatePicker::make('move_in_date')
                                ->label('تاريخ الانتقال المطلوب')
                                ->placeholder('تاريخ السكن المستهدف'),

                            Select::make('currency')
                                ->label('العملة')
                                ->options([
                                    'EGP' => 'جنيه مصري (EGP)',
                                    'USD' => 'دولار أمريكي (USD)',
                                    'EUR' => 'يورو (EUR)',
                                ])
                                ->default('EGP')
                                ->required(),
                        ]),

                        Textarea::make('preferred_locations')
                            ->label('المناطق والكمباوندات المفضلة')
                            ->placeholder('مثال: التجمع الخامس (النرجس، البنفسج، ميفيدا، هايد بارك)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('الميزانية المزدوجة (المعلنة والمؤهلة)')
                    ->description('الفصل بين ما ذكره العميل مبدئياً وبين الميزانية الواقعية بعد استيعاب أسعار السوق')
                    ->relationship('requirements')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('الميزانية المذكورة (Stated Budget)')
                                ->description('ما صرح به العميل مبدئياً في أول تواصل')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('stated_budget_min')
                                            ->label('الحد الأدنى')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),

                                        TextInput::make('stated_budget_max')
                                            ->label('الحد الأقصى')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('EGP'),
                                    ]),
                                ])->columnSpan(1),

                            Section::make('الميزانية المؤهلة (Qualified Budget)')
                                ->description('الميزانية الواقعية بعد النقاش الفعلي مع العميل')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('qualified_budget_min')
                                            ->label('الحد الأدنى الفعلي')
                                            ->numeric()
                                            ->prefix('EGP'),

                                        TextInput::make('qualified_budget_max')
                                            ->label('الحد الأقصى الفعلي')
                                            ->numeric()
                                            ->prefix('EGP'),
                                    ]),
                                ])->columnSpan(1),
                        ]),

                        Textarea::make('discussion_notes')
                            ->label('ملاحظات نقاش الميزانية وتأهيل العميل')
                            ->placeholder('سجل أسباب تعديل الميزانية وما إذا كان العميل مستعداً لزيادة السعر في حال توفر مواصفات خاصة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل طلب الإيجار')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')->label('رقم الطلب')->badge(),
                            TextEntry::make('client.name')->label('اسم العميل')->weight('bold'),
                            TextEntry::make('client.phone')->label('هاتف العميل')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('status')->label('الحالة التشغيلية')->badge(),
                            TextEntry::make('assignedAgent.name')->label('الموظف المسؤول'),
                            TextEntry::make('source')->label('المصدر'),
                            TextEntry::make('broker.name')->label('الوسيط الشريك')->default('بدون وسيط'),
                            TextEntry::make('family_status')->label('حالة الأسرة')->default('-'),
                            TextEntry::make('created_at')->label('تاريخ الإنشاء')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('notes')->label('ملاحظات الطلب')->columnSpanFull()->default('لا توجد ملاحظات'),
                        ]),
                    ]),

                Section::make('المواصفات والمتطلبات')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('requirements.property_type')->label('نوع العقار')->default('-'),
                            TextEntry::make('requirements.bedrooms')->label('عدد الغرف')->default('-'),
                            TextEntry::make('requirements.furnishing_status')->label('حالة الفرش')->badge(),
                            TextEntry::make('requirements.rental_duration')->label('مدة الإيجار')->default('-'),
                            TextEntry::make('requirements.move_in_date')->label('موعد الانتقال')->date('d/m/Y')->placeholder('فوري'),
                            TextEntry::make('requirements.currency')->label('العملة')->default('EGP'),
                            TextEntry::make('requirements.preferred_locations')->label('المواقع المفضلة')->columnSpanFull()->default('غير محدد'),
                        ]),
                    ]),

                Section::make('الميزانية المالية')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('requirements.stated_budget_max')
                                ->label('أقصى ميزانية معلنة')
                                ->money('EGP')
                                ->default('0'),

                            TextEntry::make('requirements.qualified_budget_max')
                                ->label('أقصى ميزانية مؤهلة واقعية')
                                ->money('EGP')
                                ->color('success')
                                ->weight('bold')
                                ->default('لم تحدد بعد'),

                            TextEntry::make('requirements.discussion_notes')
                                ->label('ملاحظات تأهيل الميزانية')
                                ->columnSpanFull()
                                ->default('لا توجد ملاحظات'),
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

                TextColumn::make('client.name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('client.phone')
                    ->label('الهاتف')
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        RentalRequest::STATUS_NEW => 'gray',
                        RentalRequest::STATUS_CONTACTED, RentalRequest::STATUS_SEARCHING => 'info',
                        RentalRequest::STATUS_OPTIONS_SENT, RentalRequest::STATUS_SHOWING_SCHEDULED => 'warning',
                        RentalRequest::STATUS_NEGOTIATING => 'primary',
                        RentalRequest::STATUS_CLOSED_WON => 'success',
                        RentalRequest::STATUS_CLOSED_LOST => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('requirements.property_type')
                    ->label('نوع العقار')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('requirements.furnishing_status')
                    ->label('الفرش')
                    ->toggleable(),

                TextColumn::make('requirements.preferred_locations')
                    ->label('المناطق المفضلة')
                    ->limit(25)
                    ->searchable(),

                TextColumn::make('requirements.stated_budget_max')
                    ->label('الميزانية المعلنة')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('requirements.qualified_budget_max')
                    ->label('الميزانية المؤهلة')
                    ->money('EGP')
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('nominations_count')
                    ->label('الترشيحات')
                    ->counts('nominations')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('assignedAgent.name')
                    ->label('الموظف المسؤول')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة التشغيلية')
                    ->options([
                        RentalRequest::STATUS_NEW => RentalRequest::STATUS_NEW,
                        RentalRequest::STATUS_ACTIVE => RentalRequest::STATUS_ACTIVE,
                        RentalRequest::STATUS_CONTACTED => RentalRequest::STATUS_CONTACTED,
                        RentalRequest::STATUS_SEARCHING => RentalRequest::STATUS_SEARCHING,
                        RentalRequest::STATUS_OPTIONS_SENT => RentalRequest::STATUS_OPTIONS_SENT,
                        RentalRequest::STATUS_SHOWING_SCHEDULED => RentalRequest::STATUS_SHOWING_SCHEDULED,
                        RentalRequest::STATUS_NEGOTIATING => RentalRequest::STATUS_NEGOTIATING,
                        RentalRequest::STATUS_CLOSED_WON => RentalRequest::STATUS_CLOSED_WON,
                        RentalRequest::STATUS_CLOSED_LOST => RentalRequest::STATUS_CLOSED_LOST,
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('الموظف المسؤول')
                    ->relationship('assignedAgent', 'name'),

                TrashedFilter::make()->label('سلة المحذوفات'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            NominationsRelationManager::class,
            ActivitiesRelationManager::class,
            FollowUpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRentalRequests::route('/'),
            'create' => CreateRentalRequest::route('/create'),
            'view' => ViewRentalRequest::route('/{record}'),
            'edit' => EditRentalRequest::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

<?php

namespace App\Filament\Resources\ResaleRequests;

use App\Filament\Resources\ResaleRequests\Pages\CreateResaleRequest;
use App\Filament\Resources\ResaleRequests\Pages\EditResaleRequest;
use App\Filament\Resources\ResaleRequests\Pages\ListResaleRequests;
use App\Filament\Resources\ResaleRequests\Pages\ViewResaleRequest;
use App\Filament\Resources\ResaleRequests\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\ResaleRequests\RelationManagers\FollowUpsRelationManager;
use App\Filament\Resources\ResaleRequests\RelationManagers\NominationsRelationManager;
use App\Models\Client;
use App\Models\ResaleRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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

class ResaleRequestResource extends Resource
{
    protected static ?string $model = ResaleRequest::class;

    protected static ?string $modelLabel = 'طلب ريسيل وشراء';
    protected static ?string $pluralModelLabel = 'طلبات الريسيل والشراء';
    protected static ?string $navigationLabel = 'طلبات الريسيل';
    protected static string|UnitEnum|null $navigationGroup = 'قسم الريسيل';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الطلب والعميل')
                    ->description('ربط الطلب بالعميل الموحد وتعيين الموظف المسؤول ومصدر التواصل')
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
                                ->helperText('يمكنك البحث بالاسم أو الهاتف، أو إنشاء عميل جديد مباشرة')
                                ->required(),

                            Select::make('broker_id')
                                ->label('الوسيط الشريك (اختياري)')
                                ->relationship('broker', 'name')
                                ->searchable()
                                ->preload()
                                ->placeholder('بدون وسيط (عميل مباشر)'),

                            Select::make('assigned_to')
                                ->label('مسؤول المبيعات')
                                ->relationship('assignedAgent', 'name')
                                ->default(fn () => auth()->id())
                                ->required(),

                            Select::make('source')
                                ->label('مصدر العميل')
                                ->options([
                                    'واتساب الشركة' => 'واتساب الشركة',
                                    'إعلان فيسبوك / إنستغرام' => 'إعلان فيسبوك / إنستغرام',
                                    'إعلان جوجل' => 'إعلان جوجل',
                                    'بروبرتي فايندر' => 'بروبرتي فايندر',
                                    'عقارماب' => 'عقارماب',
                                    'ترشيح عميل سابق' => 'ترشيح عميل سابق',
                                    'وسيط خارجي' => 'وسيط خارجي',
                                    'زيارة للفرع' => 'زيارة للفرع',
                                    'أخرى' => 'أخرى',
                                ])
                                ->default('واتساب الشركة')
                                ->required(),

                            Select::make('status')
                                ->label('الحالة التشغيلية')
                                ->options([
                                    ResaleRequest::STATUS_NEW => ResaleRequest::STATUS_NEW,
                                    ResaleRequest::STATUS_QUALIFICATION => ResaleRequest::STATUS_QUALIFICATION,
                                    ResaleRequest::STATUS_PROJECTS_SENT => ResaleRequest::STATUS_PROJECTS_SENT,
                                    ResaleRequest::STATUS_SITE_VISIT => ResaleRequest::STATUS_SITE_VISIT,
                                    ResaleRequest::STATUS_OFFER_NEGOTIATION => ResaleRequest::STATUS_OFFER_NEGOTIATION,
                                    ResaleRequest::STATUS_UNDER_CONTRACT => ResaleRequest::STATUS_UNDER_CONTRACT,
                                    ResaleRequest::STATUS_CLOSED_WON => ResaleRequest::STATUS_CLOSED_WON,
                                    ResaleRequest::STATUS_CLOSED_LOST => ResaleRequest::STATUS_CLOSED_LOST,
                                ])
                                ->default(ResaleRequest::STATUS_NEW)
                                ->required(),

                            Select::make('purpose')
                                ->label('الهدف من الشراء')
                                ->options([
                                    'سكن شخصي' => 'سكن شخصي وعائلي',
                                    'استثمار وإعادة بيع' => 'استثمار وإعادة بيع (Capital Appreciation)',
                                    'استثمار للإيجار' => 'استثمار للعائد الإيجاري (Rental Yield)',
                                ])
                                ->default('سكن شخصي')
                                ->required(),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات المبيعات الأولية')
                            ->placeholder('أي رغبات وتفضيلات خاصة صرح بها العميل خلال أول تواصل')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('مواصفات ومتطلبات العقار والمشروع')
                    ->description('تحديد نوع الوحدة المفضلة وحجمها والكمباوندات المستهدفة وحالة التسليم')
                    ->relationship('requirements')
                    ->schema([
                        Grid::make(3)->schema([
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
                                ->label('عدد الغرف المستهدف')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('مثال: 3'),

                            Select::make('delivery_target')
                                ->label('موعد الاستلام المستهدف')
                                ->options([
                                    'فوري' => 'استلام فوري / جاهز للمعاينة',
                                    'خلال سنة' => 'خلال سنة (تحت التشطيب)',
                                    'سنتين' => 'خلال سنتين',
                                    'تحت الإنشاء' => 'تحت الإنشاء (مقدم قليل وأقساط طويلة)',
                                ])
                                ->default('فوري')
                                ->required(),

                            Select::make('finishing_level')
                                ->label('مستوى التشطيب المطلوب')
                                ->options([
                                    'أي مستوى' => 'أي مستوى متاح',
                                    'بدون تشطيب (محارة)' => 'بدون تشطيب (على الطوب أو المحارة)',
                                    'نصف تشطيب' => 'نصف تشطيب (محارة وحلوق وأساسات)',
                                    'تشطيب كامل' => 'تشطيب كامل (جاهز للسكن)',
                                    'الترا سوبر لوكس' => 'الترا سوبر لوكس / بالتكييفات والمطبخ',
                                ])
                                ->default('تشطيب كامل')
                                ->required(),

                            Select::make('currency')
                                ->label('العملة المعتمدة')
                                ->options([
                                    'EGP' => 'جنيه مصري (EGP)',
                                    'USD' => 'دولار أمريكي (USD)',
                                ])
                                ->default('EGP')
                                ->required(),
                        ]),

                        Textarea::make('preferred_locations')
                            ->label('المناطق والكمباوندات المفضلة')
                            ->placeholder('مثال: التجمع الخامس (المستثمرين، اللوتس، هايد بارك)، مدينة زايد، أو العاصمة الإدارية')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('التأهيل المالي ونظام السداد والأقساط')
                    ->description('حساب السيولة المتاحة والقدرة على دفع الأقساط الدورية والمقدم')
                    ->relationship('requirements')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('payment_method')
                                ->label('طريقة السداد المفضلة')
                                ->options([
                                    'كاش فوري' => 'كاش فوري (Cash Only)',
                                    'تقسيط وتسهيلات' => 'تقسيط وتسهيلات سداد',
                                    'كلاهما متاح' => 'كلاهما متاح حسب الفرصة الاستثمارية',
                                ])
                                ->default('كلاهما متاح')
                                ->required(),

                            TextInput::make('total_budget')
                                ->label('أقصى ميزانية إجمالية مستهدفة')
                                ->numeric()
                                ->prefix('EGP')
                                ->default(0)
                                ->required(),

                            TextInput::make('down_payment_max')
                                ->label('أقصى مقدم متاح للدفع فوراً')
                                ->numeric()
                                ->prefix('EGP')
                                ->helperText('السيولة النقدية الجاهزة للمقدم والحجز'),

                            TextInput::make('installment_capacity')
                                ->label('قيمة القسط الدوري المستطاع')
                                ->numeric()
                                ->prefix('EGP')
                                ->helperText('المبلغ المتاح دفعه دورياً'),

                            Select::make('installment_frequency')
                                ->label('دورية سداد القسط')
                                ->options([
                                    'شهري' => 'شهري',
                                    'ربع سنوي' => 'ربع سنوي (كل 3 أشهر)',
                                    'نصف سنوي' => 'نصف سنوي (كل 6 أشهر)',
                                    'سنوي' => 'سنوي (دفعة سنوية)',
                                ])
                                ->default('ربع سنوي')
                                ->required(),

                            TextInput::make('installment_years')
                                ->label('سنوات التقسيط المفضلة')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(15)
                                ->placeholder('مثال: 5، 7، 8 سنوات'),
                        ]),

                        Textarea::make('financial_notes')
                            ->label('ملاحظات الجدارة والسيولة المالية')
                            ->placeholder('سجل توقيتات توفر السيولة (شهادات بنكية، بيع أصول، شيكات بنكية مؤجلة)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل طلب الريسيل')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')->label('رقم الطلب')->badge(),
                            TextEntry::make('client.name')->label('اسم العميل')->weight('bold'),
                            TextEntry::make('client.phone')->label('هاتف العميل')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('status')->label('الحالة التشغيلية')->badge(),
                            TextEntry::make('assignedAgent.name')->label('الموظف المسؤول'),
                            TextEntry::make('purpose')->label('الهدف من الشراء')->badge(),
                            TextEntry::make('source')->label('المصدر'),
                            TextEntry::make('broker.name')->label('الوسيط الشريك')->default('بدون وسيط (مباشر)'),
                            TextEntry::make('created_at')->label('تاريخ الإنشاء')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('notes')->label('ملاحظات المبيعات')->columnSpanFull()->default('لا توجد ملاحظات'),
                        ]),
                    ]),

                Section::make('مواصفات ومتطلبات العقار')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('requirements.property_type')->label('نوع العقار')->default('-'),
                            TextEntry::make('requirements.bedrooms')->label('عدد الغرف')->default('-'),
                            TextEntry::make('requirements.delivery_target')->label('موعد الاستلام')->badge(),
                            TextEntry::make('requirements.finishing_level')->label('مستوى التشطيب')->badge(),
                            TextEntry::make('requirements.currency')->label('العملة')->default('EGP'),
                            TextEntry::make('requirements.preferred_locations')->label('المناطق المفضلة')->columnSpanFull()->default('غير محدد'),
                        ]),
                    ]),

                Section::make('الخطة المالية وأنظمة السداد')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('requirements.payment_method')->label('طريقة السداد')->badge(),
                            TextEntry::make('requirements.total_budget')
                                ->label('أقصى ميزانية إجمالية')
                                ->money('EGP')
                                ->weight('bold')
                                ->color('success'),
                            TextEntry::make('requirements.down_payment_max')
                                ->label('أقصى مقدم متاح')
                                ->money('EGP')
                                ->placeholder('غير محدد'),
                            TextEntry::make('requirements.installment_capacity')
                                ->label('القسط الدوري المستطاع')
                                ->money('EGP')
                                ->placeholder('غير محدد'),
                            TextEntry::make('requirements.installment_frequency')->label('دورية القسط')->default('ربع سنوي'),
                            TextEntry::make('requirements.installment_years')
                                ->label('سنوات التقسيط')
                                ->formatStateUsing(fn ($state) => $state ? "{$state} سنوات" : 'غير محدد'),
                            TextEntry::make('requirements.financial_notes')
                                ->label('ملاحظات السيولة والأقساط')
                                ->columnSpanFull()
                                ->placeholder('لا توجد ملاحظات مالية إضافية'),
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
                        ResaleRequest::STATUS_NEW => 'gray',
                        ResaleRequest::STATUS_QUALIFICATION => 'info',
                        ResaleRequest::STATUS_PROJECTS_SENT, ResaleRequest::STATUS_SITE_VISIT => 'warning',
                        ResaleRequest::STATUS_OFFER_NEGOTIATION, ResaleRequest::STATUS_UNDER_CONTRACT => 'primary',
                        ResaleRequest::STATUS_CLOSED_WON => 'success',
                        ResaleRequest::STATUS_CLOSED_LOST => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('الهدف')
                    ->badge()
                    ->color('secondary')
                    ->toggleable(),

                TextColumn::make('requirements.property_type')
                    ->label('نوع العقار')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('requirements.payment_method')
                    ->label('نظام السداد')
                    ->badge()
                    ->color('info'),

                TextColumn::make('requirements.total_budget')
                    ->label('الميزانية الإجمالية')
                    ->money('EGP')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('requirements.down_payment_max')
                    ->label('المقدم المتاح')
                    ->money('EGP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('nominations_count')
                    ->label('الترشيحات')
                    ->counts('nominations')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('assignedAgent.name')
                    ->label('المسؤول')
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
                        ResaleRequest::STATUS_NEW => ResaleRequest::STATUS_NEW,
                        ResaleRequest::STATUS_QUALIFICATION => ResaleRequest::STATUS_QUALIFICATION,
                        ResaleRequest::STATUS_PROJECTS_SENT => ResaleRequest::STATUS_PROJECTS_SENT,
                        ResaleRequest::STATUS_SITE_VISIT => ResaleRequest::STATUS_SITE_VISIT,
                        ResaleRequest::STATUS_OFFER_NEGOTIATION => ResaleRequest::STATUS_OFFER_NEGOTIATION,
                        ResaleRequest::STATUS_UNDER_CONTRACT => ResaleRequest::STATUS_UNDER_CONTRACT,
                        ResaleRequest::STATUS_CLOSED_WON => ResaleRequest::STATUS_CLOSED_WON,
                        ResaleRequest::STATUS_CLOSED_LOST => ResaleRequest::STATUS_CLOSED_LOST,
                    ]),

                SelectFilter::make('purpose')
                    ->label('الهدف من الشراء')
                    ->options([
                        'سكن شخصي' => 'سكن شخصي',
                        'استثمار وإعادة بيع' => 'استثمار وإعادة بيع',
                        'استثمار للإيجار' => 'استثمار للإيجار',
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
            'index' => ListResaleRequests::route('/'),
            'create' => CreateResaleRequest::route('/create'),
            'view' => ViewResaleRequest::route('/{record}'),
            'edit' => EditResaleRequest::route('/{record}/edit'),
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

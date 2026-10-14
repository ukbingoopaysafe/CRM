<?php

namespace App\Filament\Resources\Brokers;

use App\Filament\Resources\Brokers\Pages\CreateBroker;
use App\Filament\Resources\Brokers\Pages\EditBroker;
use App\Filament\Resources\Brokers\Pages\ListBrokers;
use App\Filament\Resources\Brokers\Pages\ViewBroker;
use App\Models\Broker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use BackedEnum;
use UnitEnum;

class BrokerResource extends Resource
{
    protected static ?string $model = Broker::class;

    protected static ?string $modelLabel = 'وسيط / شريك';
    protected static ?string $pluralModelLabel = 'الوسطاء والشركاء';
    protected static ?string $navigationLabel = 'الوسطاء والشركاء';
    protected static string|UnitEnum|null $navigationGroup = 'جهات الاتصال والشركاء';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الوسيط أو الشركة الشريكة')
                    ->description('تسجيل الوسطاء الشركاء لربطهم بالطلبات والصفقات لحساب العمولات')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('اسم الوسيط أو جهة الاتصال')
                                ->placeholder('مثال: أ. كريم الشناوي')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('رقم هاتف التواصل')
                                ->placeholder('010xxxxxxxx')
                                ->tel()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('company_name')
                                ->label('اسم شركة الوساطة / المكتب العقاري')
                                ->placeholder('مثال: برايم لوكيشن للتسويق العقاري')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات الشراكة ونسبة العمولة المعتادة')
                            ->placeholder('طبيعة التعاون، مناطق التخصص، ونسب العمولة المتفق عليها مسبقاً')
                            ->rows(3)
                            ->columnSpanFull(),

                        Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل ملف الوسيط الشريك')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')->label('كود الوسيط')->badge(),
                            TextEntry::make('name')->label('اسم الوسيط')->weight('bold'),
                            TextEntry::make('company_name')->label('الشركة الشريكة')->default('وسيط فردي مستقل'),
                            TextEntry::make('phone')->label('رقم الهاتف')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('creator.name')->label('الموظف المسؤول')->default('غير محدد'),
                            TextEntry::make('created_at')->label('تاريخ التسجيل')->dateTime('d/m/Y'),
                            TextEntry::make('notes')->label('ملاحظات الشراكة والعمولة')->columnSpanFull()->default('لا توجد ملاحظات'),
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

                TextColumn::make('name')
                    ->label('اسم الوسيط')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('الشركة / المكتب')
                    ->searchable()
                    ->default('وسيط مستقل'),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('rental_requests_count')
                    ->label('طلبات الإيجار')
                    ->counts('rentalRequests')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('resale_requests_count')
                    ->label('طلبات الريسيل')
                    ->counts('resaleRequests')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrokers::route('/'),
            'create' => CreateBroker::route('/create'),
            'view' => ViewBroker::route('/{record}'),
            'edit' => EditBroker::route('/{record}/edit'),
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

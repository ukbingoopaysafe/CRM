<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Clients\RelationManagers\FollowUpsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\RentalRequestsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\ResaleRequestsRelationManager;
use App\Models\Client;
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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use UnitEnum;
use BackedEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = 'عميل';
    protected static ?string $pluralModelLabel = 'العملاء';
    protected static ?string $navigationLabel = 'العملاء';
    protected static string|UnitEnum|null $navigationGroup = 'جهات الاتصال والشركاء';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('البيانات الأساسية للعميل')
                    ->description('أدخل بيانات الاتصال مع التحقق التلقائي لمنع تكرار العميل')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('الاسم الكامل')
                                ->placeholder('مثال: م. أحمد منصور')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->placeholder('مثال: 01012345678')
                                ->tel()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, $record) {
                                    if (!$state) {
                                        return;
                                    }
                                    $normalized = Client::normalizePhone($state);
                                    $existing = Client::where('normalized_phone', $normalized)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->first();

                                    if ($existing) {
                                        Notification::make()
                                            ->warning()
                                            ->title('تنبيه: رقم الهاتف مسجل مسبقاً!')
                                            ->body("هذا الرقم مسجل بالفعل للعميل: {$existing->name} (كود #{$existing->id}) مسجل بواسطة {$existing->creator?->name}")
                                            ->persistent()
                                            ->send();
                                    }
                                })
                                ->helperText('يتم توحيد الرقم واكتشاف التكرار تلقائياً لحفظ حقوق المتابعة'),

                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->placeholder('client@example.com')
                                ->email()
                                ->maxLength(255),

                            TextInput::make('nationality')
                                ->label('الجنسية')
                                ->placeholder('مثال: مصري، سعودي، إماراتي')
                                ->default('مصري')
                                ->maxLength(100),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات واهتمامات العميل العامة')
                            ->placeholder('أي تفاصيل إضافية حول طبيعة عمل العميل أو متطلباته الخاصة')
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
                Section::make('بروفايل العميل الشامل (360° Profile)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('id')->label('كود العميل')->badge(),
                            TextEntry::make('name')->label('الاسم الكامل')->weight('bold'),
                            TextEntry::make('phone')->label('رقم الهاتف')->copyable()->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('email')->label('البريد الإلكتروني')->default('غير مسجل'),
                            TextEntry::make('nationality')->label('الجنسية')->default('مصري'),
                            TextEntry::make('creator.name')->label('الموظف المسؤول')->default('غير محدد'),
                            TextEntry::make('created_at')->label('تاريخ التسجيل')->dateTime('d/m/Y - h:i A'),
                            TextEntry::make('notes')->label('ملاحظات عامة')->columnSpanFull()->default('لا توجد ملاحظات'),
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
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedPhone),

                TextColumn::make('nationality')
                    ->label('الجنسية')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                TextColumn::make('creator.name')
                    ->label('الموظف المسؤول')
                    ->toggleable(),

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
            RentalRequestsRelationManager::class,
            ResaleRequestsRelationManager::class,
            ActivitiesRelationManager::class,
            FollowUpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
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

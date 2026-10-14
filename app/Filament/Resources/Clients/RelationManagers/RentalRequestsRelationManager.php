<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\RentalRequest;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RentalRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'rentalRequests';

    protected static ?string $title = 'طلبات الإيجار التابعة للعميل';
    protected static ?string $modelLabel = 'طلب إيجار';
    protected static ?string $pluralModelLabel = 'طلبات الإيجار';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('assigned_to')
                        ->label('الموظف المسؤول')
                        ->relationship('assignedAgent', 'name')
                        ->default(auth()->id())
                        ->required(),

                    Select::make('source')
                        ->label('مصدر الطلب')
                        ->options([
                            'فيسبوك' => 'فيسبوك',
                            'واتساب' => 'واتساب',
                            'موقع الشركة' => 'موقع الشركة',
                            'ترشيح صديق' => 'ترشيح صديق',
                            'وسيط شريك' => 'وسيط شريك',
                            'أخرى' => 'أخرى',
                        ])
                        ->default('واتساب')
                        ->required(),

                    Select::make('status')
                        ->label('حالة الطلب')
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
                        ->placeholder('مثال: أسرة مكونة من 3 أفراد'),
                ]),

                Textarea::make('notes')
                    ->label('ملاحظات الطلب')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('رقم الطلب')->badge(),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('assignedAgent.name')->label('الموظف المسؤول'),
                TextEntry::make('source')->label('المصدر'),
                TextEntry::make('family_status')->label('حالة الأسرة'),
                TextEntry::make('notes')->label('ملاحظات')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->badge(),

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
                    }),

                TextColumn::make('assignedAgent.name')
                    ->label('الموظف المسؤول'),

                TextColumn::make('requirements.preferred_locations')
                    ->label('المناطق المفضلة')
                    ->limit(25)
                    ->default('غير محددة'),

                TextColumn::make('requirements.stated_budget_max')
                    ->label('أقصى ميزانية')
                    ->money('EGP')
                    ->default('0'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة طلب إيجار جديد'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

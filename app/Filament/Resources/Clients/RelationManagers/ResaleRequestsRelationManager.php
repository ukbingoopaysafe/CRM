<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\ResaleRequest;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResaleRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'resaleRequests';

    protected static ?string $title = 'طلبات الريسيل والشراء التابعة للعميل';
    protected static ?string $modelLabel = 'طلب ريسيل';
    protected static ?string $pluralModelLabel = 'طلبات الريسيل';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('assigned_to')
                        ->label('موظف المبيعات المسؤول')
                        ->relationship('assignedAgent', 'name')
                        ->default(auth()->id())
                        ->required(),

                    Select::make('purpose')
                        ->label('الغرض من الشراء')
                        ->options([
                            'سكن شخصي' => 'سكن شخصي',
                            'استثمار وإعادة بيع' => 'استثمار وإعادة بيع',
                            'استثمار للإيجار' => 'استثمار للإيجار',
                        ])
                        ->default('سكن شخصي')
                        ->required(),

                    Select::make('status')
                        ->label('حالة الطلب')
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

                    Select::make('source')
                        ->label('مصدر الطلب')
                        ->options([
                            'فيسبوك' => 'فيسبوك',
                            'واتساب' => 'واتساب',
                            'موقع الشركة' => 'موقع الشركة',
                            'ترشيح عميل سابق' => 'ترشيح عميل سابق',
                            'وسيط شريك' => 'وسيط شريك',
                            'أخرى' => 'أخرى',
                        ])
                        ->default('واتساب')
                        ->required(),
                ]),

                Textarea::make('notes')
                    ->label('ملاحظات الطلب والقدرة المالية')
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
                TextEntry::make('purpose')->label('الغرض'),
                TextEntry::make('assignedAgent.name')->label('الموظف المسؤول'),
                TextEntry::make('source')->label('المصدر'),
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
                        ResaleRequest::STATUS_NEW => 'gray',
                        ResaleRequest::STATUS_QUALIFICATION => 'info',
                        ResaleRequest::STATUS_PROJECTS_SENT, ResaleRequest::STATUS_SITE_VISIT => 'warning',
                        ResaleRequest::STATUS_OFFER_NEGOTIATION, ResaleRequest::STATUS_UNDER_CONTRACT => 'primary',
                        ResaleRequest::STATUS_CLOSED_WON => 'success',
                        ResaleRequest::STATUS_CLOSED_LOST => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('purpose')
                    ->label('الغرض'),

                TextColumn::make('assignedAgent.name')
                    ->label('الموظف المسؤول'),

                TextColumn::make('requirements.total_budget')
                    ->label('إجمالي الميزانية')
                    ->money('EGP')
                    ->default('0'),

                TextColumn::make('requirements.payment_method')
                    ->label('طريقة السداد')
                    ->badge()
                    ->default('كلاهما متاح'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة طلب ريسيل جديد'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

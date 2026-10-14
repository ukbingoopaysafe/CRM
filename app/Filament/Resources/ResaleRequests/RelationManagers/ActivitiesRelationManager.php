<?php

namespace App\Filament\Resources\ResaleRequests\RelationManagers;

use App\Models\Activity;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'سجل الأنشطة والاتصالات الخاصة بطلب الريسيل';
    protected static ?string $modelLabel = 'نشاط';
    protected static ?string $pluralModelLabel = 'الأنشطة';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
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

                    TextInput::make('result')
                        ->label('النتيجة')
                        ->placeholder('مثال: مهتم بمشروع بادية، طلب دراسة أقساط 7 سنوات'),

                    DateTimePicker::make('occurred_at')
                        ->label('وقت الحدوث')
                        ->default(now())
                        ->required(),

                    Hidden::make('request_type')->default('resale'),
                    Hidden::make('client_id')->default(fn ($livewire) => $livewire->ownerRecord->client_id),
                    Hidden::make('user_id')->default(fn () => auth()->id()),
                ]),

                Textarea::make('description')
                    ->label('تفاصيل ما حدث في التواصل')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')->label('نوع النشاط')->badge(),
                TextEntry::make('occurred_at')->label('الوقت')->dateTime('d/m/Y - h:i A'),
                TextEntry::make('user.name')->label('الموظف'),
                TextEntry::make('result')->label('النتيجة'),
                TextEntry::make('description')->label('التفاصيل')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('نوع النشاط')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Activity::TYPE_CALL => 'info',
                        Activity::TYPE_WHATSAPP => 'success',
                        Activity::TYPE_SHOWING => 'warning',
                        Activity::TYPE_DEAL_CLOSED => 'primary',
                        default => 'secondary',
                    }),

                TextColumn::make('user.name')
                    ->label('بواسطة'),

                TextColumn::make('description')
                    ->label('تفاصيل النشاط')
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('result')
                    ->label('النتيجة')
                    ->badge()
                    ->color('gray')
                    ->default('-'),

                TextColumn::make('occurred_at')
                    ->label('التاريخ والوقت')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('تسجيل تواصل / نشاط جديد'),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

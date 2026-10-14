<?php

namespace App\Filament\Resources\RentalRequests\RelationManagers;

use App\Models\FollowUp;
use Filament\Actions\Action;
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
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'متابعات وتذكيرات طلب الإيجار';
    protected static ?string $modelLabel = 'متابعة';
    protected static ?string $pluralModelLabel = 'المتابعات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('action_required')
                        ->label('الإجراء المطلوب')
                        ->placeholder('مثال: الاتصال لتأكيد موعد معاينة ميفيدا')
                        ->required()
                        ->columnSpan(2),

                    DateTimePicker::make('due_at')
                        ->label('موعد الاستحقاق')
                        ->default(now()->addDay())
                        ->required(),

                    Select::make('assigned_to')
                        ->label('الموظف المسؤول')
                        ->relationship('assignedAgent', 'name')
                        ->default(auth()->id())
                        ->required(),

                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            FollowUp::STATUS_PENDING => FollowUp::STATUS_PENDING,
                            FollowUp::STATUS_COMPLETED => FollowUp::STATUS_COMPLETED,
                            FollowUp::STATUS_CANCELLED => FollowUp::STATUS_CANCELLED,
                        ])
                        ->default(FollowUp::STATUS_PENDING)
                        ->required(),

                    Hidden::make('request_type')->default('rental'),
                    Hidden::make('client_id')->default(fn ($livewire) => $livewire->ownerRecord->client_id),
                ]),

                Textarea::make('notes')
                    ->label('تفاصيل وملاحظات إضافية')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('action_required')->label('الإجراء المطلوب')->weight('bold'),
                TextEntry::make('due_at')->label('موعد الاستحقاق')->dateTime('d/m/Y - h:i A'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('assignedAgent.name')->label('الموظف المسؤول'),
                TextEntry::make('notes')->label('ملاحظات')->columnSpanFull(),
                TextEntry::make('completion_notes')->label('تقرير الإكمال')->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_required')
            ->defaultSort('due_at', 'asc')
            ->columns([
                TextColumn::make('action_required')
                    ->label('الإجراء المطلوب')
                    ->weight('bold'),

                TextColumn::make('due_at')
                    ->label('موعد الاستحقاق')
                    ->dateTime('d/m/Y - h:i A')
                    ->sortable()
                    ->color(fn (FollowUp $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (FollowUp $record): string => match (true) {
                        $record->status === FollowUp::STATUS_COMPLETED => 'success',
                        $record->status === FollowUp::STATUS_CANCELLED => 'gray',
                        $record->isOverdue() => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('assignedAgent.name')
                    ->label('الموظف المسؤول'),
            ])
            ->headerActions([
                CreateAction::make()->label('جدولة متابعة جديدة لهذا الطلب'),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('إكمال')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (FollowUp $record): bool => $record->status === FollowUp::STATUS_PENDING)
                    ->form([
                        Textarea::make('completion_notes')
                            ->label('نتيجة المتابعة')
                            ->placeholder('ماذا حدث خلال المتابعة؟')
                            ->required(),
                    ])
                    ->action(function (FollowUp $record, array $data): void {
                        $record->markCompleted($data['completion_notes']);

                        Notification::make()
                            ->success()
                            ->title('تم إكمال المتابعة بنجاح')
                            ->send();
                    }),

                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

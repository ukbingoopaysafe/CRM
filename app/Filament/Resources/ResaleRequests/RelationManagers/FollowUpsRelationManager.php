<?php

namespace App\Filament\Resources\ResaleRequests\RelationManagers;

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

    protected static ?string $title = 'متابعات وتذكيرات طلب الريسيل';
    protected static ?string $modelLabel = 'متابعة';
    protected static ?string $pluralModelLabel = 'المتابعات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('action_required')
                        ->label('الإجراء المطلوب')
                        ->placeholder('مثال: إرسال جدول أقساط بادية 8 سنوات للعميل')
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

                    Hidden::make('request_type')->default('resale'),
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
                TextEntry::make('due_at')->label('تاريخ الاستحقاق')->dateTime('d/m/Y - h:i A'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('assignedAgent.name')->label('الموظف المكلف'),
                TextEntry::make('notes')->label('ملاحظات المتابعة')->columnSpanFull(),
                TextEntry::make('result_notes')->label('تقرير الإنجاز')->columnSpanFull(),
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
                    ->weight('bold')
                    ->limit(40),

                TextColumn::make('due_at')
                    ->label('تاريخ الاستحقاق')
                    ->dateTime('d/m/Y - h:i A')
                    ->color(fn (FollowUp $record): string => $record->isOverdue() ? 'danger' : 'gray')
                    ->weight(fn (FollowUp $record): string => $record->isOverdue() ? 'bold' : 'normal')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FollowUp::STATUS_COMPLETED => 'success',
                        FollowUp::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('assignedAgent.name')
                    ->label('المسؤول'),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()->label('إضافة تذكير / متابعة جديدة'),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('إنجاز سريع')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (FollowUp $record): bool => $record->status === FollowUp::STATUS_PENDING)
                    ->schema([
                        Textarea::make('result_notes')
                            ->label('ماذا تم في هذه المتابعة؟')
                            ->placeholder('تم الاتصال بالعميل وإرسال جدول الأقساط وحددنا موعد معاينة')
                            ->required(),
                    ])
                    ->action(function (FollowUp $record, array $data): void {
                        $record->markCompleted($data['result_notes']);

                        Notification::make()
                            ->title('تم إنجاز المتابعة بنجاح')
                            ->body('تم تسجيل النتيجة ونقل تفاصيلها إلى سجل الأنشطة تلقائياً.')
                            ->success()
                            ->send();
                    }),

                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}

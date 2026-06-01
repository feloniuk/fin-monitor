<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonobankAccountResource\Pages;
use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
use App\Services\Monobank\MonobankSyncService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonobankAccountResource extends Resource
{
    protected static ?string $model = MonobankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Рахунки';
    protected static ?string $modelLabel = 'Рахунок';
    protected static ?string $pluralModelLabel = 'Рахунки';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => strtoupper($state ?? '—')),
                Tables\Columns\TextColumn::make('iban')
                    ->label('IBAN')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('masked_pan')
                    ->label('Картка')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ($state ?? '—')),
                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Валюта')
                    ->formatStateUsing(fn (int $state) => CurrencyHelper::code($state)),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс')
                    ->formatStateUsing(fn ($state, MonobankAccount $record) => CurrencyHelper::format($state, $record->currency_code))
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Кредитний ліміт')
                    ->formatStateUsing(fn ($state, MonobankAccount $record) => $state > 0 ? CurrencyHelper::format($state, $record->currency_code) : '—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активний')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_statement_sync_at')
                    ->label('Остання синхронізація')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('sync_statements')
                    ->label('Синхронізувати')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->modalHeading('Синхронізація транзакцій')
                    ->modalDescription('Ліміт Monobank API: 1 запит на 60 сек., макс. період — 31 день на запит. Якщо обраний період більше 31 дня, він буде розбитий на частини з автоматичною затримкою 65 сек. між ними. Потрібен запущений queue worker (php artisan queue:work).')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('Дата від')
                            ->default(now()->subMonth()->format('Y-m-d')),
                        \Filament\Forms\Components\DatePicker::make('date_to')
                            ->label('Дата до')
                            ->default(now()->format('Y-m-d')),
                    ])
                    ->action(function (MonobankAccount $record, array $data) {
                        try {
                            $service = new MonobankSyncService();
                            $from = \Carbon\Carbon::parse($data['date_from']);
                            $to = \Carbon\Carbon::parse($data['date_to']);

                            $days = $from->diffInDays($to);
                            $chunks = (int) ceil($days / 31);
                            $estimatedTime = $chunks > 1 ? " (≈" . ($chunks * 65) . " сек. через rate limit)" : '';

                            $service->dispatchStatementSync($record, $from, $to);

                            Notification::make()
                                ->title('Синхронізацію заплановано')
                                ->body("Запитів: {$chunks}{$estimatedTime}. Транзакції завантажуються у фоновому режимі.")
                                ->success()
                                ->duration(10000)
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Помилка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonobankAccounts::route('/'),
        ];
    }
}

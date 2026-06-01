<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonobankTokenResource\Pages;
use App\Models\MonobankToken;
use App\Services\Monobank\MonobankClient;
use App\Services\Monobank\MonobankSyncService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MonobankTokenResource extends Resource
{
    protected static ?string $model = MonobankToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Налаштування Monobank';
    protected static ?string $modelLabel = 'Токен Monobank';
    protected static ?string $pluralModelLabel = 'Токени Monobank';
    protected static ?string $navigationGroup = 'Налаштування';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Forms\Components\TextInput::make('token')
                    ->label('API Токен')
                    ->required()
                    ->password()
                    ->revealable()
                    ->helperText('Отримайте токен на api.monobank.ua'),
                Forms\Components\TextInput::make('client_name')
                    ->label("Ім'я клієнта")
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label("Ім'я клієнта")
                    ->default('—'),
                Tables\Columns\TextColumn::make('accounts_count')
                    ->counts('accounts')
                    ->label('Рахунків'),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Остання синхронізація')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Додано')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Тест з\'єднання')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Тест з\'єднання з Monobank')
                    ->modalDescription('Буде виконано запит до Monobank API (ліміт: 1 запит на 60 сек). Після цього запиту потрібно зачекати хвилину перед наступним.')
                    ->modalSubmitActionLabel('Виконати')
                    ->action(function (MonobankToken $record) {
                        $wait = MonobankClient::secondsUntilNextCall();
                        if ($wait > 0) {
                            Notification::make()
                                ->title('Зачекайте')
                                ->body("До наступного запиту залишилось {$wait} сек. (ліміт Monobank: 1 запит / 60 сек.)")
                                ->warning()
                                ->duration(8000)
                                ->send();
                            return;
                        }

                        try {
                            $client = new MonobankClient($record->token);
                            $info = $client->getClientInfo();
                            $name = $info['name'] ?? 'Невідомий';
                            $record->update(['client_name' => $name]);

                            Notification::make()
                                ->title("З'єднання успішне!")
                                ->body("Клієнт: {$name}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Помилка з\'єднання')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sync')
                    ->label('Синхронізувати')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Синхронізація з Monobank')
                    ->modalDescription('Буде завантажено інформацію про клієнта та список рахунків. Ліміт Monobank API: 1 запит на 60 секунд. Після синхронізації зачекайте хвилину перед іншими операціями.')
                    ->modalSubmitActionLabel('Синхронізувати')
                    ->action(function (MonobankToken $record) {
                        $wait = MonobankClient::secondsUntilNextCall();
                        if ($wait > 0) {
                            Notification::make()
                                ->title('Зачекайте')
                                ->body("До наступного запиту залишилось {$wait} сек. (ліміт Monobank: 1 запит / 60 сек.)")
                                ->warning()
                                ->duration(8000)
                                ->send();
                            return;
                        }

                        try {
                            $service = new MonobankSyncService();
                            $service->syncClientInfo($record);

                            Notification::make()
                                ->title('Синхронізацію завершено')
                                ->body('Інформацію про клієнта та рахунки оновлено. Зачекайте 60 сек. перед наступним запитом.')
                                ->success()
                                ->duration(8000)
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Помилка синхронізації')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()->label('Редагувати'),
                Tables\Actions\DeleteAction::make()->label('Видалити'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonobankTokens::route('/'),
            'create' => Pages\CreateMonobankToken::route('/create'),
            'edit' => Pages\EditMonobankToken::route('/{record}/edit'),
        ];
    }
}

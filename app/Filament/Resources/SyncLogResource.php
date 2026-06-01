<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Models\SyncLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Журнал синхронізації';
    protected static ?string $modelLabel = 'Запис синхронізації';
    protected static ?string $pluralModelLabel = 'Журнал синхронізації';
    protected static ?string $navigationGroup = 'Налаштування';
    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Час')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'client_info' => 'Інфо клієнта',
                        'statements' => 'Транзакції',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'success' => 'Успішно',
                        'failed' => 'Помилка',
                        'running' => 'Виконується',
                        'pending' => 'Очікує',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('account.display_name')
                    ->label('Рахунок')
                    ->default('—'),
                Tables\Columns\TextColumn::make('date_from')
                    ->label('Від')
                    ->dateTime('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('date_to')
                    ->label('До')
                    ->dateTime('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('records_fetched')
                    ->label('Записів'),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Помилка')
                    ->limit(50)
                    ->tooltip(fn (SyncLog $record) => $record->error_message)
                    ->default('—'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
        ];
    }
}

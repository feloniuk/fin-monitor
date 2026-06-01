<?php

namespace App\Filament\Widgets;

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Останні транзакції';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Transaction::query()->latest('time')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('time')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Опис')
                    ->limit(40),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сума')
                    ->formatStateUsing(fn ($state, Transaction $record) => CurrencyHelper::format($state, $record->currency_code))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('account.display_name')
                    ->label('Рахунок'),
            ])
            ->paginated(false);
    }
}

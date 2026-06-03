<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Filters;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Транзакції';
    protected static ?string $modelLabel = 'Транзакція';
    protected static ?string $pluralModelLabel = 'Транзакції';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('time', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('time')
                    ->label('Дата/час')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Опис')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (Transaction $record) => $record->description),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сума')
                    ->formatStateUsing(fn ($state, Transaction $record) => CurrencyHelper::format($state, $record->currency_code))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс після')
                    ->formatStateUsing(fn ($state, Transaction $record) => CurrencyHelper::format($state, $record->currency_code))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('account.display_name')
                    ->label('Рахунок')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mcc')
                    ->label('MCC')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Комісія')
                    ->formatStateUsing(fn ($state, Transaction $record) => $state != 0 ? CurrencyHelper::format($state, $record->currency_code) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cashback_amount')
                    ->label('Кешбек')
                    ->formatStateUsing(fn ($state, Transaction $record) => $state != 0 ? CurrencyHelper::format($state, $record->currency_code) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Коментар')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('counter_name')
                    ->label('Контрагент')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('counter_edrpou')
                    ->label('ЄДРПОУ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('hold')
                    ->label('Холд')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('monobank_account_id')
                    ->label('Рахунок')
                    ->options(fn () => MonobankAccount::all()->pluck('display_name', 'id')->toArray())
                    ->multiple()
                    ->preload(),
                Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Від')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('До')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('time', '>=', $date))
                            ->when($data['date_to'], fn ($q, $date) => $q->whereDate('time', '<=', $date));
                    }),
                Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'income' => 'Доходи',
                        'expense' => 'Витрати',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'income') {
                            return $query->where('amount', '>', 0);
                        }
                        if ($data['value'] === 'expense') {
                            return $query->where('amount', '<', 0);
                        }
                        return $query;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Деталі')
                    ->form([
                        Forms\Components\TextInput::make('time')->label('Дата/час')->disabled(),
                        Forms\Components\TextInput::make('description')->label('Опис')->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Сума')
                            ->formatStateUsing(fn ($state, $record) => CurrencyHelper::format($state, $record->currency_code))
                            ->disabled(),
                        Forms\Components\TextInput::make('balance')
                            ->label('Баланс після')
                            ->formatStateUsing(fn ($state, $record) => CurrencyHelper::format($state, $record->currency_code))
                            ->disabled(),
                        Forms\Components\TextInput::make('comment')->label('Коментар')->disabled(),
                        Forms\Components\TextInput::make('counter_name')->label('Контрагент')->disabled(),
                        Forms\Components\TextInput::make('counter_edrpou')->label('ЄДРПОУ')->disabled(),
                        Forms\Components\TextInput::make('counter_iban')->label('IBAN контрагента')->disabled(),
                        Forms\Components\TextInput::make('mcc')->label('MCC')->disabled(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Комісія')
                            ->formatStateUsing(fn ($state, $record) => CurrencyHelper::format($state, $record->currency_code))
                            ->disabled(),
                        Forms\Components\TextInput::make('cashback_amount')
                            ->label('Кешбек')
                            ->formatStateUsing(fn ($state, $record) => CurrencyHelper::format($state, $record->currency_code))
                            ->disabled(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}

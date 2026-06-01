<?php

namespace App\Filament\Widgets;

use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountBalancesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $accounts = MonobankAccount::where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            return [
                Stat::make('Рахунки', 'Немає підключених рахунків')
                    ->description('Додайте токен Monobank у налаштуваннях')
                    ->icon('heroicon-o-credit-card'),
            ];
        }

        return $accounts->map(function (MonobankAccount $account) {
            $type = strtoupper($account->type ?? 'Рахунок');
            $currency = CurrencyHelper::code($account->currency_code);

            return Stat::make(
                "{$type} ({$currency})",
                CurrencyHelper::format($account->balance, $account->currency_code)
            )
                ->description($account->iban ? "IBAN: {$account->iban}" : ($account->masked_pan ? implode(', ', $account->masked_pan) : ''))
                ->icon('heroicon-o-credit-card')
                ->color($account->balance >= 0 ? 'success' : 'danger');
        })->toArray();
    }
}

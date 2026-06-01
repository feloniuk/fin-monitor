<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IncomeExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Доходи / Витрати (6 місяців)';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->startOfMonth());
        }

        $incomes = [];
        $expenses = [];
        $labels = [];

        foreach ($months as $month) {
            $start = $month->copy();
            $end = $month->copy()->endOfMonth();

            $income = Transaction::whereBetween('time', [$start, $end])
                ->where('amount', '>', 0)
                ->sum('amount');

            $expense = Transaction::whereBetween('time', [$start, $end])
                ->where('amount', '<', 0)
                ->sum('amount');

            $incomes[] = round($income / 100, 2);
            $expenses[] = round(abs($expense) / 100, 2);
            $labels[] = $month->translatedFormat('M Y');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Доходи',
                    'data' => $incomes,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Витрати',
                    'data' => $expenses,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

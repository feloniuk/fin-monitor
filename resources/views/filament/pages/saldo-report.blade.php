<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-document-chart-bar">
                Сформувати звіт
            </x-filament::button>
        </div>
    </form>

    @if($reportGenerated)
        @foreach($accountReports as $report)
            <div class="mt-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                    {{ $report['account_name'] }}
                </h3>

                @if($report['transaction_count'] === 0)
                    <div class="p-4 rounded-lg bg-warning-50 dark:bg-warning-400/10 text-warning-700 dark:text-warning-400 border border-warning-300 dark:border-warning-400/20">
                        <div class="flex items-center gap-2 font-semibold mb-1">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                            Немає транзакцій за обраний період
                        </div>
                        <p class="text-sm">
                            Спочатку синхронізуйте транзакції: перейдіть у розділ <strong>Рахунки</strong>, натисніть <strong>"Синхронізувати"</strong> і вкажіть потрібний діапазон дат.
                        </p>
                    </div>
                @elseif(count($report['periods']) > 0)
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        Транзакцій за період: {{ $report['transaction_count'] }}
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                                    <th class="px-4 py-3 text-left font-medium">Період</th>
                                    <th class="px-4 py-3 text-right font-medium">Вхідний баланс</th>
                                    <th class="px-4 py-3 text-right font-medium">Доходи</th>
                                    <th class="px-4 py-3 text-right font-medium">Витрати</th>
                                    <th class="px-4 py-3 text-right font-medium">Вихідний баланс</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach($report['periods'] as $index => $row)
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-white/[0.02]' }}">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['period'] }}</td>
                                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 tabular-nums">{{ $this->formatAmount($row['opening_balance'], $report['currency_code']) }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium tabular-nums">{{ $this->formatAmount($row['income'], $report['currency_code']) }}</td>
                                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-medium tabular-nums">{{ $this->formatAmount($row['expense'], $report['currency_code']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white tabular-nums">{{ $this->formatAmount($row['closing_balance'], $report['currency_code']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</x-filament-panels::page>

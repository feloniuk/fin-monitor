<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-6 flex items-center justify-end gap-3">
            @if($reportGenerated)
                <x-filament::button
                    wire:click="exportExcel"
                    color="success"
                    icon="heroicon-o-arrow-down-tray"
                    outlined
                >
                    Експорт Excel
                </x-filament::button>
            @endif
            <x-filament::button type="submit" icon="heroicon-o-document-chart-bar">
                Сформувати звіт
            </x-filament::button>
        </div>
    </form>

    @if($reportGenerated)

        {{-- Summary section --}}
        @if(count($summaryReport) > 0)
            <div class="mt-8">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-500" />
                    Зведена інформація по обраних рахунках
                </h2>

                @foreach($summaryReport as $summary)
                    <div class="mb-6">

                        {{-- Current balance card --}}
                        <div class="mb-3 flex items-center gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Поточний сумарний залишок:</span>
                            <span class="text-base font-bold text-primary-600 dark:text-primary-400 tabular-nums">
                                {{ $this->formatAmount($summary['current_balance'], $summary['currency_code']) }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">({{ $summary['account_count'] }} {{ $summary['account_count'] === 1 ? 'рахунок' : ($summary['account_count'] < 5 ? 'рахунки' : 'рахунків') }})</span>
                        </div>

                        @if(count($summary['periods']) > 0)
                            <div class="rounded-xl border-2 border-primary-200 dark:border-primary-800 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                                            <th class="px-4 py-3 text-left font-semibold">Період</th>
                                            <th class="px-4 py-3 text-right font-semibold">Вхідний залишок</th>
                                            <th class="px-4 py-3 text-right font-semibold">Доходи</th>
                                            <th class="px-4 py-3 text-right font-semibold">Витрати</th>
                                            <th class="px-4 py-3 text-right font-semibold">Вихідний залишок</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-primary-100 dark:divide-primary-900/30">
                                        @foreach($summary['periods'] as $index => $row)
                                            <tr class="{{ $index % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-primary-50/30 dark:bg-primary-900/10' }}">
                                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $row['period'] }}</td>
                                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 tabular-nums">{{ $this->formatAmount($row['opening_balance'], $summary['currency_code']) }}</td>
                                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium tabular-nums">{{ $this->formatAmount($row['income'], $summary['currency_code']) }}</td>
                                                <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-medium tabular-nums">{{ $this->formatAmount($row['expense'], $summary['currency_code']) }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white tabular-nums">{{ $this->formatAmount($row['closing_balance'], $summary['currency_code']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-primary-100 dark:bg-primary-900/40 border-t-2 border-primary-200 dark:border-primary-700">
                                            <td class="px-4 py-3 font-bold text-primary-800 dark:text-primary-200">Поточний залишок</td>
                                            <td class="px-4 py-3" colspan="3"></td>
                                            <td class="px-4 py-3 text-right font-bold text-primary-700 dark:text-primary-300 tabular-nums text-base">
                                                {{ $this->formatAmount($summary['current_balance'], $summary['currency_code']) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 dark:border-white/10 my-6"></div>
        @endif

        {{-- Individual account reports --}}
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
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-4">
                        <span>Транзакцій за період: {{ $report['transaction_count'] }}</span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>Поточний залишок:
                            <span class="font-semibold text-gray-700 dark:text-gray-200 tabular-nums">
                                {{ $this->formatAmount($report['current_balance'], $report['currency_code']) }}
                            </span>
                        </span>
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

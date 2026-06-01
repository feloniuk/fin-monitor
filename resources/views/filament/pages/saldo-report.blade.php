<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Сформувати звіт
            </x-filament::button>
        </div>
    </form>

    @if(count($reportData) > 0)
        <div class="mt-6">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold">Період</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold text-right">Вхідний баланс</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold text-right">Доходи</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold text-right">Витрати</th>
                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-semibold text-right">Вихідний баланс</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 font-medium">{{ $row['period'] }}</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-right">{{ $this->formatAmount($row['opening_balance']) }}</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-right text-green-600 dark:text-green-400">{{ $this->formatAmount($row['income']) }}</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-right text-red-600 dark:text-red-400">{{ $this->formatAmount($row['expense']) }}</td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-right font-semibold">{{ $this->formatAmount($row['closing_balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif($account_id)
        <div class="mt-6 text-center text-gray-500 dark:text-gray-400">
            Немає даних для обраного періоду. Натисніть "Сформувати звіт".
        </div>
    @endif
</x-filament-panels::page>

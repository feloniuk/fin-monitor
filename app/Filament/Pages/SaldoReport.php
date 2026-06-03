<?php

namespace App\Filament\Pages;

use App\Exports\SaldoReportExport;
use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
use App\Models\Transaction;
use App\Services\Monobank\MonobankSyncService;
use Carbon\Carbon;
use Filament\Forms;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class SaldoReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Звіт Сальдо';
    protected static ?string $title = 'Звіт Сальдо';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.saldo-report';

    public array $account_ids = [];
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $group_by = 'month';
    public array $accountReports = [];
    public array $summaryReport = [];
    public bool $reportGenerated = false;

    public function mount(): void
    {
        $this->date_from = now()->startOfYear()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Select::make('account_ids')
                            ->label('Рахунки')
                            ->options(fn () => MonobankAccount::all()->pluck('display_name', 'id')->toArray())
                            ->multiple()
                            ->required(),
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Від')
                            ->required(),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('До')
                            ->required(),
                        Forms\Components\Select::make('group_by')
                            ->label('Групування')
                            ->options([
                                'month' => 'По місяцях',
                                'quarter' => 'По кварталах',
                            ])
                            ->default('month')
                            ->required(),
                    ]),
            ]);
    }

    public function generateReport(): void
    {
        $this->validate([
            'account_ids' => 'required|array|min:1',
            'account_ids.*' => 'exists:monobank_accounts,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $this->reportGenerated = true;
        $this->accountReports = [];
        $this->summaryReport = [];

        $from = Carbon::parse($this->date_from);
        $to = Carbon::parse($this->date_to);
        $service = new MonobankSyncService();

        foreach ($this->account_ids as $accountId) {
            $account = MonobankAccount::findOrFail($accountId);

            $transactionCount = Transaction::where('monobank_account_id', $account->id)
                ->whereBetween('time', [$from, $to])
                ->count();

            $periods = $service->getSaldoReport($account, $from->copy(), $to->copy(), $this->group_by);

            $this->accountReports[] = [
                'account_name' => $account->display_name,
                'currency_code' => $account->currency_code,
                'current_balance' => $account->balance,
                'transaction_count' => $transactionCount,
                'periods' => $periods,
            ];
        }

        $this->summaryReport = $this->buildSummary();
    }

    private function buildSummary(): array
    {
        $byCurrency = [];

        foreach ($this->accountReports as $report) {
            $cc = $report['currency_code'];

            if (!isset($byCurrency[$cc])) {
                $byCurrency[$cc] = [
                    'currency_code' => $cc,
                    'current_balance' => 0,
                    'account_count' => 0,
                    'periods' => [],
                ];
            }

            $byCurrency[$cc]['current_balance'] += $report['current_balance'];
            $byCurrency[$cc]['account_count']++;

            foreach ($report['periods'] as $i => $period) {
                if (!isset($byCurrency[$cc]['periods'][$i])) {
                    $byCurrency[$cc]['periods'][$i] = [
                        'period' => $period['period'],
                        'opening_balance' => 0,
                        'income' => 0,
                        'expense' => 0,
                        'closing_balance' => 0,
                    ];
                }
                $byCurrency[$cc]['periods'][$i]['opening_balance'] += $period['opening_balance'];
                $byCurrency[$cc]['periods'][$i]['income'] += $period['income'];
                $byCurrency[$cc]['periods'][$i]['expense'] += $period['expense'];
                $byCurrency[$cc]['periods'][$i]['closing_balance'] += $period['closing_balance'];
            }
        }

        return array_values($byCurrency);
    }

    public function exportExcel(): BinaryFileResponse
    {
        $filename = 'saldo-report-' . $this->date_from . '-' . $this->date_to . '.xlsx';

        return Excel::download(
            new SaldoReportExport($this->accountReports, $this->summaryReport, $this->date_from, $this->date_to),
            $filename
        );
    }

    public function formatAmount(int $amount, int $currencyCode): string
    {
        return CurrencyHelper::format($amount, $currencyCode);
    }
}

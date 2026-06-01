<?php

namespace App\Filament\Pages;

use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
use App\Models\Transaction;
use App\Services\Monobank\MonobankSyncService;
use Carbon\Carbon;
use Filament\Forms;
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
                'transaction_count' => $transactionCount,
                'periods' => $periods,
            ];
        }
    }

    public function formatAmount(int $amount, int $currencyCode): string
    {
        return CurrencyHelper::format($amount, $currencyCode);
    }
}

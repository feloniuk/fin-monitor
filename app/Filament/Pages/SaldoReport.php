<?php

namespace App\Filament\Pages;

use App\Helpers\CurrencyHelper;
use App\Models\MonobankAccount;
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

    public ?int $account_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $group_by = 'month';
    public array $reportData = [];
    public ?int $currencyCode = null;

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
                        Forms\Components\Select::make('account_id')
                            ->label('Рахунок')
                            ->options(fn () => MonobankAccount::all()->pluck('display_name', 'id')->toArray())
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
            'account_id' => 'required|exists:monobank_accounts,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $account = MonobankAccount::findOrFail($this->account_id);
        $this->currencyCode = $account->currency_code;

        $service = new MonobankSyncService();
        $this->reportData = $service->getSaldoReport(
            $account,
            Carbon::parse($this->date_from),
            Carbon::parse($this->date_to),
            $this->group_by
        );
    }

    public function formatAmount(int $amount): string
    {
        return CurrencyHelper::format($amount, $this->currencyCode ?? 980);
    }
}

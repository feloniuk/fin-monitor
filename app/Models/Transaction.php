<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'monobank_account_id',
        'transaction_id',
        'time',
        'description',
        'mcc',
        'amount',
        'operation_amount',
        'currency_code',
        'commission_rate',
        'cashback_amount',
        'balance',
        'hold',
        'comment',
        'receipt_id',
        'counter_edrpou',
        'counter_iban',
        'counter_name',
    ];

    protected function casts(): array
    {
        return [
            'time' => 'datetime',
            'hold' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MonobankAccount::class, 'monobank_account_id');
    }

    public function getAmountUahAttribute(): float
    {
        return $this->amount / 100;
    }

    public function getIsIncomeAttribute(): bool
    {
        return $this->amount > 0;
    }
}

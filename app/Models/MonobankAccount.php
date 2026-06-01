<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonobankAccount extends Model
{
    protected $fillable = [
        'monobank_token_id',
        'account_id',
        'currency_code',
        'balance',
        'credit_limit',
        'masked_pan',
        'type',
        'iban',
        'is_active',
        'last_statement_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'masked_pan' => 'array',
            'is_active' => 'boolean',
            'last_statement_sync_at' => 'datetime',
        ];
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(MonobankToken::class, 'monobank_token_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function getCurrencySymbolAttribute(): string
    {
        return \App\Helpers\CurrencyHelper::symbol($this->currency_code);
    }

    public function getBalanceUahAttribute(): float
    {
        return $this->balance / 100;
    }

    public function getDisplayNameAttribute(): string
    {
        $currency = \App\Helpers\CurrencyHelper::code($this->currency_code);
        $type = $this->type ? strtoupper($this->type) : '';
        $pan = $this->masked_pan ? implode(', ', $this->masked_pan) : '';

        return trim("{$type} {$currency} {$pan}");
    }
}

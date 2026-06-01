<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $fillable = [
        'monobank_account_id',
        'type',
        'status',
        'date_from',
        'date_to',
        'records_fetched',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'datetime',
            'date_to' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MonobankAccount::class, 'monobank_account_id');
    }
}

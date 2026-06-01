<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonobankToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'client_name',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(MonobankAccount::class);
    }
}

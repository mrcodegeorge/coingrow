<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTransaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_TRANSFER = 'transfer';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    protected $fillable = [
        'user_id',
        'type',
        'source_sub_account_id',
        'destination_sub_account_id',
        'amount',
        'frequency',
        'next_run_at',
        'active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_run_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceSubAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class, 'source_sub_account_id');
    }

    public function destinationSubAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class, 'destination_sub_account_id');
    }
}

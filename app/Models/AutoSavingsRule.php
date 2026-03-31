<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoSavingsRule extends Model
{
    use HasFactory;

    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_PER_DEPOSIT = 'per_deposit';

    protected $fillable = [
        'user_id',
        'sub_account_id',
        'type',
        'value',
        'frequency',
        'active',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'active' => 'boolean',
            'next_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class);
    }
}

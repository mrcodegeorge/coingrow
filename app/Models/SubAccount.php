<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SubAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'balance',
        'target',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'target' => 'decimal:2',
            'locked' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentSplit(): HasOne
    {
        return $this->hasOne(PaymentSplit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function targetProgress(): float
    {
        if (! $this->target || (float) $this->target <= 0.0) {
            return 0.0;
        }

        return min(100, round((((float) $this->balance) / ((float) $this->target)) * 100, 1));
    }

    public function shouldAutoUnlock(): bool
    {
        return $this->locked && $this->target !== null && (float) $this->balance >= (float) $this->target;
    }
}

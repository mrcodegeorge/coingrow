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

    public function remainingToTarget(): float
    {
        if (! $this->target) {
            return 0.0;
        }

        return max(round((float) $this->target - (float) $this->balance, 2), 0);
    }

    public function estimatedCompletionLabel(): string
    {
        if (! $this->target || $this->remainingToTarget() <= 0) {
            return 'Target reached';
        }

        $monthlySavingsRate = (float) $this->transactions()
            ->whereIn('type', ['sub_account_deposit', 'auto_split_in', 'transfer'])
            ->where('created_at', '>=', now()->subDays(90))
            ->get()
            ->sum(function (Transaction $transaction) {
                $direction = collect($transaction->tags ?? []);

                if ($transaction->type === 'transfer' && $direction->contains('transfer_out')) {
                    return -1 * (float) $transaction->amount;
                }

                return (float) $transaction->amount;
            }) / 3;

        if ($monthlySavingsRate <= 0) {
            return 'Needs more funding history';
        }

        $monthsRemaining = ceil($this->remainingToTarget() / $monthlySavingsRate);

        return $monthsRemaining <= 1
            ? 'About 1 month left'
            : "About {$monthsRemaining} months left";
    }
}

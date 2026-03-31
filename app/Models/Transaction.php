<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    public const CATEGORY_OPTIONS = [
        'income',
        'savings',
        'rent',
        'food',
        'transport',
        'utilities',
        'lifestyle',
        'bills',
        'transfer',
        'other',
    ];

    protected $fillable = [
        'account_id',
        'sub_account_id',
        'related_sub_account_id',
        'type',
        'external_reference',
        'category',
        'amount',
        'balance_after',
        'description',
        'note',
        'tags',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at' => 'datetime',
            'tags' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function subAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class);
    }

    public function relatedSubAccount(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class, 'related_sub_account_id');
    }
}

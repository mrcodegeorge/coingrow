<?php

namespace App\Services;

use App\Models\Account;
use App\Models\SubAccount;
use App\Models\Transaction;

class TransactionLoggerService
{
    public function log(
        Account $account,
        string $type,
        float $amount,
        float $balanceAfter,
        string $description,
        ?SubAccount $subAccount = null,
        array $context = []
    ): Transaction {
        return Transaction::create([
            'account_id' => $account->id,
            'sub_account_id' => $subAccount?->id,
            'related_sub_account_id' => $context['related_sub_account_id'] ?? null,
            'type' => $type,
            'category' => $context['category'] ?? null,
            'amount' => number_format($amount, 2, '.', ''),
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
            'description' => $description,
            'note' => $context['note'] ?? null,
            'tags' => $context['tags'] ?? null,
            'created_at' => now(),
        ]);
    }
}

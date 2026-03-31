<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;

class VirtualAccountService
{
    public function generateAccountNumber(): string
    {
        do {
            $accountNumber = 'CG'.random_int(10000000, 99999999);
        } while (Account::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }

    public function generateAccountName(User $user): string
    {
        return strtoupper($user->name).' - COINGROW';
    }
}

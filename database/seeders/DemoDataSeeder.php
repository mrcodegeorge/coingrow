<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BankingService;
use App\Services\TransactionLoggerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::create([
                'username' => 'demo',
                'name' => 'Demo Customer',
                'password' => 'Password123',
            ]);

            /** @var BankingService $bankingService */
            $bankingService = app(BankingService::class);
            /** @var TransactionLoggerService $logger */
            $logger = app(TransactionLoggerService::class);

            $account = $bankingService->createPrimaryAccountForUser($user->id);
            $logger->log($account, 'account_created', 0, 0, 'Demo primary account created.');

            $emergency = $bankingService->createSubAccount($account, 'Emergency Fund', 5000, true, 40);
            $rent = $bankingService->createSubAccount($account, 'Rent Wallet', 2500, false, 25);
            $travel = $bankingService->createSubAccount($account, 'Travel Vault', 4000, true, 15);

            $bankingService->depositToMain($account, 3000, 'Initial salary funding.');
            $bankingService->depositToSubAccount($emergency, 250, 'Manual top-up for emergency fund.');
            $bankingService->withdrawFromMain($account, 180, 'Utility bill payment.');
            $bankingService->depositToSubAccount($rent, 120, 'Additional rent reserve.');
            $bankingService->depositToMain($account, 1500, 'Freelance income.');
            $bankingService->withdrawFromSubAccount($rent->fresh(), 100, 'Partial rent payment.');
            $bankingService->depositToSubAccount($travel, 700, 'Trip planning contribution.');
        });
    }
}

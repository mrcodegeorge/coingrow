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

            $bankingService->depositToMain($account, 3000, ['category' => 'income', 'tags' => ['salary']], 'Initial salary funding.');
            $bankingService->depositToSubAccount($emergency, 250, ['category' => 'savings', 'tags' => ['manual_top_up']], 'Manual top-up for emergency fund.');
            $bankingService->withdrawFromMain($account, 180, ['category' => 'utilities', 'tags' => ['bill']], 'Utility bill payment.');
            $bankingService->depositToSubAccount($rent, 120, ['category' => 'rent', 'tags' => ['reserve']], 'Additional rent reserve.');
            $bankingService->depositToMain($account, 1500, ['category' => 'income', 'tags' => ['freelance']], 'Freelance income.');
            $bankingService->withdrawFromSubAccount($rent->fresh(), 100, ['category' => 'rent', 'tags' => ['payment']], 'Partial rent payment.');
            $bankingService->depositToSubAccount($travel, 700, ['category' => 'savings', 'tags' => ['travel_goal']], 'Trip planning contribution.');
            $bankingService->transferBetweenSubAccounts($rent->fresh(), $travel->fresh(), 150, ['category' => 'transfer', 'note' => 'Rebalanced rent reserve into travel fund.', 'tags' => ['rebalance']]);
        });
    }
}

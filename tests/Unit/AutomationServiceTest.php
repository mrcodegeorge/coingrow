<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AutoSavingsRule;
use App\Models\ScheduledTransaction;
use App\Models\SubAccount;
use App\Models\User;
use App\Services\AutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_deposit_auto_savings_moves_money_into_target_wallet(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['user_id' => $user->id, 'balance' => 0]);
        $wallet = SubAccount::create([
            'account_id' => $account->id,
            'name' => 'Emergency',
            'balance' => 0,
            'target' => 1000,
            'locked' => false,
        ]);

        AutoSavingsRule::create([
            'user_id' => $user->id,
            'sub_account_id' => $wallet->id,
            'type' => AutoSavingsRule::TYPE_PERCENTAGE,
            'value' => 10,
            'frequency' => AutoSavingsRule::FREQUENCY_PER_DEPOSIT,
            'active' => true,
        ]);

        $this->app->make(\App\Services\BankingService::class)->depositToMain($account, 200, ['category' => 'income']);

        $this->assertSame(180.0, (float) $account->fresh()->balance);
        $this->assertSame(20.0, (float) $wallet->fresh()->balance);
    }

    public function test_due_scheduled_deposit_moves_money_from_main_to_wallet(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['user_id' => $user->id, 'balance' => 300]);
        $wallet = SubAccount::create([
            'account_id' => $account->id,
            'name' => 'Rent',
            'balance' => 0,
            'target' => 500,
            'locked' => false,
        ]);

        ScheduledTransaction::create([
            'user_id' => $user->id,
            'type' => ScheduledTransaction::TYPE_DEPOSIT,
            'destination_sub_account_id' => $wallet->id,
            'amount' => 75,
            'frequency' => ScheduledTransaction::FREQUENCY_WEEKLY,
            'next_run_at' => now()->subMinute(),
            'active' => true,
            'description' => 'Weekly rent top-up',
        ]);

        $result = app(AutomationService::class)->processDueAutomations();

        $this->assertSame(1, $result['scheduled_transactions']);
        $this->assertSame(225.0, (float) $account->fresh()->balance);
        $this->assertSame(75.0, (float) $wallet->fresh()->balance);
    }
}

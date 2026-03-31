<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\SubAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BankingRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_sub_accounts_cannot_be_withdrawn_from(): void
    {
        $user = User::factory()->create();
        $account = app(BankingService::class)->createPrimaryAccountForUser($user);

        $subAccount = SubAccount::create([
            'account_id' => $account->id,
            'name' => 'Goal',
            'balance' => 200,
            'target' => 500,
            'locked' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(BankingService::class)->withdrawFromSubAccount($subAccount, 50);
    }

    public function test_transfers_move_money_and_log_both_sides(): void
    {
        $user = User::factory()->create();
        $account = app(BankingService::class)->createPrimaryAccountForUser($user);

        $source = SubAccount::create([
            'account_id' => $account->id,
            'name' => 'Travel',
            'balance' => 300,
            'target' => 500,
            'locked' => false,
        ]);

        $destination = SubAccount::create([
            'account_id' => $account->id,
            'name' => 'Rent',
            'balance' => 50,
            'target' => 300,
            'locked' => false,
        ]);

        app(BankingService::class)->transferBetweenSubAccounts($source, $destination, 75, [
            'category' => 'transfer',
            'note' => 'Monthly rebalance',
            'tags' => ['rebalance'],
        ]);

        $this->assertSame(225.0, (float) $source->fresh()->balance);
        $this->assertSame(125.0, (float) $destination->fresh()->balance);
        $this->assertCount(2, Transaction::where('type', 'transfer')->get());
    }
}

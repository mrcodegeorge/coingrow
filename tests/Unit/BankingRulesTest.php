<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\SubAccount;
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
        $account = Account::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

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
}

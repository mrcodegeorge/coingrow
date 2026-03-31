<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use App\Services\BankingService;
use App\Services\FinancialInsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialInsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_predictive_metrics_and_recommendations(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['user_id' => $user->id, 'balance' => 0]);

        $emergency = app(BankingService::class)->createSubAccount($account, 'Emergency', 1000, false, 30);
        $rent = app(BankingService::class)->createSubAccount($account, 'Rent', 500, false, 20);

        app(BankingService::class)->depositToMain($account, 1000, ['category' => 'income'], 'Salary');
        app(BankingService::class)->withdrawFromMain($account->fresh(), 200, ['category' => 'food'], 'Food spend');
        app(BankingService::class)->depositToSubAccount($emergency, 150, ['category' => 'savings'], 'Emergency top-up');
        app(BankingService::class)->withdrawFromSubAccount($rent->fresh(), 50, ['category' => 'rent'], 'Rent payment');

        $result = app(FinancialInsightsService::class)->buildDashboardInsights($account->fresh());

        $this->assertGreaterThan(0, $result['predictive']['avgMonthlyIncome']);
        $this->assertGreaterThan(0, $result['predictive']['avgMonthlyExpense']);
        $this->assertNotEmpty($result['cards']);
    }
}

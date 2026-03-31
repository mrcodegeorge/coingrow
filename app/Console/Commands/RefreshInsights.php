<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FinancialInsightsService;
use Illuminate\Console\Command;

class RefreshInsights extends Command
{
    protected $signature = 'coingrow:refresh-insights';

    protected $description = 'Rebuild financial insights for all users.';

    public function handle(FinancialInsightsService $financialInsightsService): int
    {
        $count = 0;

        User::query()->with('account')->chunk(100, function ($users) use ($financialInsightsService, &$count) {
            foreach ($users as $user) {
                if (! $user->account) {
                    continue;
                }

                $financialInsightsService->refreshUserInsights($user);
                $count++;
            }
        });

        $this->info("Refreshed insights for {$count} users.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialInsight;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

class FinancialInsightsService
{
    public function buildDashboardInsights(Account $account): array
    {
        $transactions = $account->transactions()
            ->with(['subAccount', 'relatedSubAccount'])
            ->where('created_at', '>=', now()->subDays(90))
            ->latest('created_at')
            ->get();

        $predictive = $this->predictiveMetrics($account, $transactions);
        $insights = $this->generateInsights($account, $transactions, $predictive);

        return [
            'cards' => $insights,
            'predictive' => $predictive,
        ];
    }

    public function refreshUserInsights(User $user): Collection
    {
        $account = $user->account()->with('subAccounts.paymentSplit')->firstOrFail();
        $dashboardInsights = $this->buildDashboardInsights($account);

        $user->financialInsights()->delete();

        return collect($dashboardInsights['cards'])->map(function (array $insight) use ($user) {
            return FinancialInsight::create([
                'user_id' => $user->id,
                'type' => $insight['type'],
                'title' => $insight['title'],
                'message' => $insight['message'],
                'value' => $insight['value'] ?? null,
                'meta' => $insight['meta'] ?? null,
                'generated_at' => now(),
            ]);
        });
    }

    protected function generateInsights(Account $account, Collection $transactions, array $predictive): array
    {
        $suggestedWeeklySavings = max(round(($predictive['avgMonthlyIncome'] * 0.15) / 4, 2), 0);
        $topExpenseCategory = $transactions
            ->filter(fn (Transaction $transaction) => in_array($transaction->type, ['withdrawal', 'sub_account_withdrawal'], true))
            ->groupBy(fn (Transaction $transaction) => $transaction->category ?? 'other')
            ->map(fn (Collection $items) => $items->sum(fn (Transaction $transaction) => (float) $transaction->amount))
            ->sortDesc()
            ->keys()
            ->first();

        $suggestedSplit = $account->subAccounts
            ->sortByDesc(fn ($subAccount) => $subAccount->remainingToTarget())
            ->first();

        return array_values(array_filter([
            [
                'type' => FinancialInsight::TYPE_SAVINGS,
                'title' => 'Smart savings target',
                'message' => sprintf('You can likely save %.2f weekly based on your recent income flow.', $suggestedWeeklySavings),
                'value' => $suggestedWeeklySavings,
            ],
            $topExpenseCategory ? [
                'type' => FinancialInsight::TYPE_SPENDING,
                'title' => 'Spending watch',
                'message' => sprintf('Your highest outgoing category is %s. A small cut here could improve your monthly runway.', ucfirst($topExpenseCategory)),
                'value' => null,
                'meta' => ['category' => $topExpenseCategory],
            ] : null,
            [
                'type' => FinancialInsight::TYPE_RUNWAY,
                'title' => 'Balance runway',
                'message' => $predictive['runwayDays'] > 999
                    ? 'Your current balance is well covered by recent spending behavior.'
                    : sprintf('At your recent burn rate, your main balance may last about %d days.', $predictive['runwayDays']),
                'value' => $predictive['runwayDays'],
            ],
            $suggestedSplit ? [
                'type' => FinancialInsight::TYPE_SPLIT,
                'title' => 'Suggested split adjustment',
                'message' => sprintf('You are furthest from the %s goal. Consider directing more deposits there.', $suggestedSplit->name),
                'value' => $suggestedSplit->remainingToTarget(),
                'meta' => ['sub_account_id' => $suggestedSplit->id],
            ] : null,
        ]));
    }

    protected function predictiveMetrics(Account $account, Collection $transactions): array
    {
        $monthlyIncome = $transactions
            ->filter(fn (Transaction $transaction) => in_array($transaction->type, ['deposit'], true))
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->format('Y-m'))
            ->map(fn (Collection $items) => $items->sum(fn (Transaction $transaction) => (float) $transaction->amount));

        $monthlyExpenses = $transactions
            ->filter(fn (Transaction $transaction) => in_array($transaction->type, ['withdrawal', 'sub_account_withdrawal'], true))
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->format('Y-m'))
            ->map(fn (Collection $items) => $items->sum(fn (Transaction $transaction) => (float) $transaction->amount));

        $avgMonthlyIncome = round($monthlyIncome->avg() ?? 0, 2);
        $avgMonthlyExpense = round($monthlyExpenses->avg() ?? 0, 2);
        $burnRate = round($avgMonthlyExpense, 2);
        $dailyBurnRate = $burnRate > 0 ? $burnRate / 30 : 0;
        $runwayDays = $dailyBurnRate > 0 ? (int) floor(((float) $account->balance) / $dailyBurnRate) : 9999;

        return [
            'avgMonthlyIncome' => $avgMonthlyIncome,
            'avgMonthlyExpense' => $avgMonthlyExpense,
            'burnRate' => $burnRate,
            'runwayDays' => $runwayDays,
        ];
    }
}

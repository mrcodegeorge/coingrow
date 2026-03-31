<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function dashboardFor(Account $account): array
    {
        $transactions = $account->transactions()
            ->with(['subAccount', 'relatedSubAccount'])
            ->orderBy('created_at')
            ->get();

        return [
            'incomeExpense' => $this->incomeVsExpenses($transactions),
            'walletDistribution' => $this->walletDistribution($account),
            'savingsGrowth' => $this->savingsGrowth($transactions),
        ];
    }

    protected function incomeVsExpenses(Collection $transactions): array
    {
        $period = CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), '1 month', now()->startOfMonth());
        $months = collect($period)->map(fn (Carbon $month) => $month->format('M Y'));

        $incomeMap = [];
        $expenseMap = [];

        foreach ($transactions as $transaction) {
            $key = $transaction->created_at->format('M Y');

            if (in_array($transaction->type, ['deposit', 'sub_account_deposit'], true)) {
                $incomeMap[$key] = ($incomeMap[$key] ?? 0) + (float) $transaction->amount;
            }

            if (in_array($transaction->type, ['withdrawal', 'sub_account_withdrawal'], true)) {
                $expenseMap[$key] = ($expenseMap[$key] ?? 0) + (float) $transaction->amount;
            }
        }

        return [
            'labels' => $months->values()->all(),
            'income' => $months->map(fn (string $month) => round($incomeMap[$month] ?? 0, 2))->values()->all(),
            'expenses' => $months->map(fn (string $month) => round($expenseMap[$month] ?? 0, 2))->values()->all(),
        ];
    }

    protected function walletDistribution(Account $account): array
    {
        $wallets = $account->subAccounts()->orderBy('name')->get();

        return [
            'labels' => $wallets->pluck('name')->all(),
            'values' => $wallets->map(fn ($wallet) => round((float) $wallet->balance, 2))->all(),
        ];
    }

    protected function savingsGrowth(Collection $transactions): array
    {
        $period = CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), '1 month', now()->startOfMonth());
        $months = collect($period)->map(fn (Carbon $month) => $month->copy());
        $running = 0.0;
        $series = [];

        foreach ($months as $month) {
            $monthlyNet = $transactions
                ->filter(function (Transaction $transaction) use ($month) {
                    return $transaction->created_at->isSameMonth($month)
                        && in_array($transaction->type, ['sub_account_deposit', 'auto_split_in', 'transfer_in', 'sub_account_withdrawal', 'transfer_out'], true);
                })
                ->sum(function (Transaction $transaction) {
                    return in_array($transaction->type, ['sub_account_withdrawal', 'transfer_out'], true)
                        ? -1 * (float) $transaction->amount
                        : (float) $transaction->amount;
                });

            $running += $monthlyNet;
            $series[] = round(max($running, 0), 2);
        }

        return [
            'labels' => $months->map(fn (Carbon $month) => $month->format('M Y'))->values()->all(),
            'values' => $series,
        ];
    }
}

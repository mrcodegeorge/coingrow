<?php

namespace App\Http\Controllers;

use App\Models\AutoSavingsRule;
use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $account = $user->account()->with([
            'subAccounts.paymentSplit',
        ])->firstOrFail();

        $transactions = $this->transactionsFor($request);
        $splitTotal = $account->subAccounts->sum(fn ($subAccount) => (float) ($subAccount->paymentSplit?->percentage ?? 0));
        $analytics = $this->analyticsService->dashboardFor($account);

        return view('dashboard.index', [
            'account' => $account,
            'subAccounts' => $account->subAccounts->sortBy('name')->values(),
            'transactions' => $transactions,
            'filters' => [
                'mode' => $request->string('mode', 'all')->toString(),
                'account' => $request->string('account', 'all')->toString(),
                'category' => $request->string('category', 'all')->toString(),
            ],
            'splitTotal' => round($splitTotal, 2),
            'analytics' => $analytics,
            'notifications' => $user->notifications()->latest()->limit(6)->get(),
            'unreadNotifications' => $user->unreadNotifications()->count(),
            'transactionCategories' => Transaction::CATEGORY_OPTIONS,
            'autoSavingsRules' => $user->autoSavingsRules()->with('subAccount')->latest()->get(),
            'scheduledTransactions' => $user->scheduledTransactions()->with(['sourceSubAccount', 'destinationSubAccount'])->latest()->get(),
            'automationOptions' => [
                'ruleTypes' => [AutoSavingsRule::TYPE_FIXED, AutoSavingsRule::TYPE_PERCENTAGE],
                'ruleFrequencies' => [AutoSavingsRule::FREQUENCY_DAILY, AutoSavingsRule::FREQUENCY_WEEKLY, AutoSavingsRule::FREQUENCY_PER_DEPOSIT],
                'scheduledTypes' => [ScheduledTransaction::TYPE_DEPOSIT, ScheduledTransaction::TYPE_TRANSFER],
                'scheduledFrequencies' => [ScheduledTransaction::FREQUENCY_DAILY, ScheduledTransaction::FREQUENCY_WEEKLY, ScheduledTransaction::FREQUENCY_MONTHLY],
            ],
        ]);
    }

    protected function transactionsFor(Request $request): LengthAwarePaginator
    {
        $account = $request->user()->account;
        $mode = $request->string('mode', 'all')->toString();
        $accountFilter = $request->string('account', 'all')->toString();
        $category = $request->string('category', 'all')->toString();

        $query = Transaction::query()
            ->with(['subAccount', 'relatedSubAccount'])
            ->where('account_id', $account->id)
            ->latest();

        if ($accountFilter === 'main') {
            $query->whereNull('sub_account_id');
        } elseif (str_starts_with($accountFilter, 'sub:')) {
            $query->where('sub_account_id', (int) str_replace('sub:', '', $accountFilter));
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $perPage = match ($mode) {
            'recent' => 10,
            'detailed' => 50,
            default => 15,
        };

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }
}

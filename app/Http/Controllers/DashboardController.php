<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $account = $user->account()->with([
            'subAccounts.paymentSplit',
        ])->firstOrFail();

        $transactions = $this->transactionsFor($request);
        $splitTotal = $account->subAccounts->sum(fn ($subAccount) => (float) ($subAccount->paymentSplit?->percentage ?? 0));

        return view('dashboard.index', [
            'account' => $account,
            'subAccounts' => $account->subAccounts->sortBy('name')->values(),
            'transactions' => $transactions,
            'filters' => [
                'mode' => $request->string('mode', 'all')->toString(),
                'account' => $request->string('account', 'all')->toString(),
            ],
            'splitTotal' => round($splitTotal, 2),
        ]);
    }

    protected function transactionsFor(Request $request): LengthAwarePaginator
    {
        $account = $request->user()->account;
        $mode = $request->string('mode', 'all')->toString();
        $accountFilter = $request->string('account', 'all')->toString();

        $query = Transaction::query()
            ->with('subAccount')
            ->where('account_id', $account->id)
            ->latest();

        if ($accountFilter === 'main') {
            $query->whereNull('sub_account_id');
        } elseif (str_starts_with($accountFilter, 'sub:')) {
            $query->where('sub_account_id', (int) str_replace('sub:', '', $accountFilter));
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

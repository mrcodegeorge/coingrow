@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
        $totalMoney = (float) $account->balance + (float) $subAccounts->sum('balance');
        $revenueTotal = (float) collect($analytics['incomeExpense']['income'] ?? [])->sum();
        $expenseTotal = (float) collect($analytics['incomeExpense']['expenses'] ?? [])->sum();
        $miniIncomeBars = collect($analytics['incomeExpense']['income'] ?? [])->take(-8)->map(function ($value) use ($revenueTotal) {
            return $revenueTotal > 0 ? ($value / $revenueTotal) * 100 : 18;
        })->values();
        $miniExpenseBars = collect($analytics['incomeExpense']['expenses'] ?? [])->take(-8)->map(function ($value) use ($expenseTotal) {
            return $expenseTotal > 0 ? ($value / $expenseTotal) * 100 : 18;
        })->values();
        $recentTransactions = $transactions->getCollection()->take(6);
        $currentMonthIncome = (float) collect($analytics['incomeExpense']['income'] ?? [])->last();
        $currentMonthExpense = (float) collect($analytics['incomeExpense']['expenses'] ?? [])->last();
    @endphp

    <div x-data="dashboardApp()" class="fin-shell">
        <aside class="fin-sidebar">
            <div class="flex flex-col items-center gap-5">
                <div class="sidebar-icon sidebar-icon-active">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 12.5 12 4l9 8.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5.5 10.5V20h13V10.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <a href="#overview" class="sidebar-icon sidebar-icon-muted" title="Dashboard">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="4" y="4" width="7" height="7" rx="1.5"/>
                        <rect x="13" y="4" width="7" height="4" rx="1.5"/>
                        <rect x="13" y="10" width="7" height="10" rx="1.5"/>
                        <rect x="4" y="13" width="7" height="7" rx="1.5"/>
                    </svg>
                </a>
                <a href="#wallets" class="sidebar-icon sidebar-icon-muted" title="Accounts">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="3.5" y="6" width="17" height="12" rx="2"/>
                        <path d="M16 12h4.5" stroke-linecap="round"/>
                        <circle cx="16" cy="12" r="1"/>
                    </svg>
                </a>
                <a href="#transactions" class="sidebar-icon sidebar-icon-muted" title="Transactions">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M7 7h10M7 12h10M7 17h6" stroke-linecap="round"/>
                        <rect x="4" y="4" width="16" height="16" rx="2"/>
                    </svg>
                </a>
                <a href="#split-system" class="sidebar-icon sidebar-icon-muted" title="Sub-Accounts">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 4v16M4 8h8M12 16h8" stroke-linecap="round"/>
                        <circle cx="6" cy="8" r="2"/>
                        <circle cx="18" cy="16" r="2"/>
                    </svg>
                </a>
                <button type="button" class="sidebar-icon sidebar-icon-muted" title="Settings" @click="openModal('change-password')">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7Z"/>
                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.34l-.06.06A2 2 0 1 1 4.29 16.9l.06-.06A1.7 1.7 0 0 0 4.7 15a1.7 1.7 0 0 0-1.54-1H3a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 1.54-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06A2 2 0 1 1 7.06 4.3l.06.06A1.7 1.7 0 0 0 9 4.7a1.7 1.7 0 0 0 1-1.54V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1 1.54 1.7 1.7 0 0 0 1.87-.34l.06-.06A2 2 0 1 1 19.7 7.06l-.06.06A1.7 1.7 0 0 0 19.3 9a1.7 1.7 0 0 0 1.54 1H21a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <div class="mt-auto flex flex-col items-center gap-3">
                <button type="button" class="sidebar-icon sidebar-icon-muted" title="Notifications" @click="openModal('notifications')">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/>
                        <path d="M10 21a2 2 0 0 0 4 0" stroke-linecap="round"/>
                    </svg>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-icon sidebar-icon-muted" title="Log out">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </aside>

        <div class="fin-main lg:flex">
            <main class="fin-center">
                <div class="space-y-6">
                    <section id="overview" class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                        <x-card class="overflow-hidden">
                            <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                                <div class="max-w-xl">
                                    <div class="summary-chip">Dashboard</div>
                                    <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl">
                                        ${{ number_format((float) $account->balance, 2) }}
                                    </h1>
                                    <div class="mt-5 grid gap-3 text-sm text-slate-500 sm:grid-cols-2">
                                        <div>
                                            <div class="balance-copy-label">Your Money</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">${{ number_format($totalMoney, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="balance-copy-label">Current Month Flow</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">${{ number_format($currentMonthIncome - $currentMonthExpense, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="balance-copy-label">Credit Limit</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">$0.00</div>
                                        </div>
                                        <div>
                                            <div class="balance-copy-label">Active Wallets</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">{{ $subAccounts->count() }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button type="button" class="btn-primary" @click="openModal('main-withdraw')">Make Payment</button>
                                    <button type="button" class="btn-secondary" @click="openModal('main-deposit')">Request / Requisites</button>
                                </div>
                            </div>
                        </x-card>

                        <div class="grid gap-4">
                            <x-stat-card label="Total Revenue" :amount="'$' . number_format($revenueTotal, 2)" tone="income" :bars="$miniIncomeBars->all()" />
                            <x-stat-card label="Total Expense" :amount="'$' . number_format($expenseTotal, 2)" tone="expense" :bars="$miniExpenseBars->all()" />
                        </div>
                    </section>

                    <x-card id="money-flow" title="Money Flow" subtitle="Income and outcome across your account activity">
                        <x-slot:actions>
                            <div class="flex items-center gap-2 rounded-full bg-stone-100 p-1 text-sm">
                                <button type="button" class="rounded-full px-3 py-1.5 transition" :class="chartRange === 'monthly' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'" @click="chartRange = 'monthly'">Monthly</button>
                                <button type="button" class="rounded-full px-3 py-1.5 transition" :class="chartRange === 'yearly' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'" @click="chartRange = 'yearly'">Yearly</button>
                            </div>
                        </x-slot:actions>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <div class="info-tile">
                                <div class="info-tile-label">Money On Start</div>
                                <div class="info-tile-value">${{ number_format((float) $account->balance, 2) }}</div>
                            </div>
                            <div class="info-tile">
                                <div class="info-tile-label">Income</div>
                                <div class="info-tile-value text-emerald-600">${{ number_format($revenueTotal, 2) }}</div>
                            </div>
                            <div class="info-tile">
                                <div class="info-tile-label">Outcome</div>
                                <div class="info-tile-value text-rose-500">${{ number_format($expenseTotal, 2) }}</div>
                            </div>
                        </div>

                        <div class="mt-6 h-80 rounded-[1.5rem] border border-stone-200 bg-[#f9f7f2] p-4">
                            <canvas id="moneyFlowChart"></canvas>
                        </div>
                    </x-card>

                    <section class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                        <x-card id="wallets" title="Sub-Accounts" subtitle="Goal-based wallets with locking and transfer controls">
                            <x-slot:actions>
                                <button type="button" class="btn-primary" @click="openModal('create-wallet')">Create Wallet</button>
                            </x-slot:actions>

                            <div class="space-y-4">
                                @forelse ($subAccounts as $subAccount)
                                    <article class="rounded-[1.35rem] border border-stone-200 bg-[#faf8f4] p-4">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-lg font-semibold text-slate-950">{{ $subAccount->name }}</h3>
                                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $subAccount->locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                        {{ $subAccount->locked ? 'Locked' : 'Active' }}
                                                    </span>
                                                </div>
                                                <div class="mt-3 text-2xl font-semibold text-slate-950">${{ number_format((float) $subAccount->balance, 2) }}</div>
                                                <div class="mt-2 text-sm text-slate-500">
                                                    Target: {{ $subAccount->target ? '$' . number_format((float) $subAccount->target, 2) : 'No target set' }}
                                                </div>
                                                <div class="tiny-detail">Remaining: {{ $subAccount->target ? '$' . number_format($subAccount->remainingToTarget(), 2) : 'Not applicable' }}</div>
                                                <div class="tiny-detail">{{ $subAccount->estimatedCompletionLabel() }}</div>
                                                <div class="progress-track">
                                                    <div class="progress-fill" style="width: {{ min(100, $subAccount->targetProgress()) }}%"></div>
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-4 text-xs font-medium uppercase tracking-[0.16em] text-slate-500">
                                                    <span>{{ number_format($subAccount->targetProgress(), 1) }}% completed</span>
                                                    <span>{{ number_format((float) ($subAccount->paymentSplit?->percentage ?? 0), 2) }}% split</span>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-2 lg:max-w-[240px] lg:justify-end">
                                                <button type="button" class="btn-secondary" @click="openSubAction('deposit', '{{ route('sub-accounts.deposit', $subAccount) }}', '{{ $subAccount->name }}')">Deposit</button>
                                                <button type="button" class="btn-secondary" @click="openSubAction('withdraw', '{{ route('sub-accounts.withdraw', $subAccount) }}', '{{ $subAccount->name }}')">Withdraw</button>
                                                <button type="button" class="btn-secondary" @click="openTransferModal('{{ route('sub-accounts.transfer', $subAccount) }}', '{{ $subAccount->name }}')">Transfer</button>
                                                <form method="POST" action="{{ route('sub-accounts.lock', $subAccount) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="locked" value="{{ $subAccount->locked ? 0 : 1 }}">
                                                    <button type="submit" class="btn-secondary">{{ $subAccount->locked ? 'Unlock' : 'Lock' }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('sub-accounts.destroy', $subAccount) }}" onsubmit="return confirm('Delete {{ $subAccount->name }}? This only works when the balance is zero.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="rounded-[1.35rem] border border-dashed border-stone-300 bg-[#faf8f4] p-8 text-center text-sm text-slate-500">
                                        No sub-accounts yet. Create one to start saving toward a goal.
                                    </div>
                                @endforelse
                            </div>
                        </x-card>
 
                        <div class="space-y-6">
                            <x-card id="split-system" title="Payment Split" subtitle="Deposit automation across savings wallets">
                                <x-slot:actions>
                                    <button type="button" class="btn-secondary" @click="openModal('split-settings')">Manage</button>
                                </x-slot:actions>

                                <div class="space-y-4">
                                    @forelse ($subAccounts as $subAccount)
                                        @php $percentage = (float) ($subAccount->paymentSplit?->percentage ?? 0); @endphp
                                        <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <div class="text-sm font-semibold text-slate-900">{{ $subAccount->name }}</div>
                                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">
                                                        {{ $percentage > 0 ? number_format($percentage, 2) . '% of deposits' : 'Not included' }}
                                                    </div>
                                                </div>
                                                <div class="text-sm font-semibold text-slate-900">${{ number_format((float) $subAccount->balance, 2) }}</div>
                                            </div>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: {{ min(100, $percentage) }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-5 text-sm text-slate-500">
                                            Create a wallet to start using payment splits.
                                        </div>
                                    @endforelse
                                </div>
                            </x-card>

                            <x-card title="Smart Insights" subtitle="Financial guidance from your recent activity">
                                <div class="grid gap-3">
                                    @foreach ($insights['cards'] as $insight)
                                        <article class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $insight['type'] }}</div>
                                            <h3 class="mt-2 text-base font-semibold text-slate-950">{{ $insight['title'] }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $insight['message'] }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            </x-card>
                        </div>
                    </section>

                    <x-card id="transactions" title="Transactions" subtitle="Detailed ledger, categories, tags, and account filters">
                        <x-slot:actions>
                            <button type="button" class="btn-secondary" @click="openModal('notifications')">Alerts</button>
                        </x-slot:actions>

                        <div class="grid gap-3 lg:grid-cols-4">
                            <form method="GET" action="{{ route('dashboard') }}" class="contents">
                                <select name="mode" class="filter-select">
                                    <option value="all" @selected($filters['mode'] === 'all')>All</option>
                                    <option value="recent" @selected($filters['mode'] === 'recent')>Recent 10</option>
                                    <option value="detailed" @selected($filters['mode'] === 'detailed')>Detailed 50</option>
                                </select>
                                <select name="account" class="filter-select">
                                    <option value="all" @selected($filters['account'] === 'all')>All accounts</option>
                                    <option value="main" @selected($filters['account'] === 'main')>Main only</option>
                                    @foreach ($subAccounts as $subAccount)
                                        <option value="sub:{{ $subAccount->id }}" @selected($filters['account'] === 'sub:' . $subAccount->id)>{{ $subAccount->name }}</option>
                                    @endforeach
                                </select>
                                <select name="category" class="filter-select">
                                    <option value="all" @selected($filters['category'] === 'all')>All categories</option>
                                    @foreach ($transactionCategories as $category)
                                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucfirst($category) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-secondary">Filter</button>
                            </form>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($transactions as $transaction)
                                @php
                                    $isIncoming = in_array($transaction->type, ['deposit', 'auto_split', 'scheduled_deposit'], true);
                                    $accountLabel = $transaction->subAccount?->name ?? 'Main account';
                                    $accountTrail = $transaction->relatedSubAccount ? $accountLabel . ' -> ' . $transaction->relatedSubAccount->name : $accountLabel;
                                @endphp
                                <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="summary-chip !bg-white !px-2.5 !py-1 !text-[11px]">{{ str_replace('_', ' ', $transaction->type) }}</span>
                                                @if ($transaction->category)
                                                    <span class="rounded-full bg-stone-200 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600">{{ $transaction->category }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-3 text-sm font-medium text-slate-900">{{ $transaction->description }}</div>
                                            @if ($transaction->note)
                                                <div class="mt-1 text-sm text-slate-500">{{ $transaction->note }}</div>
                                            @endif
                                            @if (! empty($transaction->tags))
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($transaction->tags as $tag)
                                                        <span class="rounded-full border border-stone-200 px-2.5 py-1 text-[11px] text-slate-500">#{{ $tag }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="mt-2 text-xs uppercase tracking-[0.16em] text-slate-400">{{ $accountTrail }} | {{ $transaction->created_at->format('M d, Y h:i A') }}</div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <div class="text-base font-semibold {{ $isIncoming ? 'text-emerald-600' : 'text-rose-500' }}">
                                                {{ $isIncoming ? '+' : '-' }}${{ number_format((float) $transaction->amount, 2) }}
                                            </div>
                                            <div class="mt-1 text-xs text-slate-400">Balance after ${{ number_format((float) $transaction->balance_after, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-6 text-sm text-slate-500">
                                    Transaction activity will appear here as soon as you start using the platform.
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-6">
                            {{ $transactions->links() }}
                        </div>
                    </x-card>

                    <section class="grid gap-6 lg:grid-cols-2">
                        <x-card title="Auto-Savings Rules" subtitle="Fixed or percentage-based savings automation">
                            <x-slot:actions>
                                <button type="button" class="btn-primary" @click="openModal('auto-savings')">Add Rule</button>
                            </x-slot:actions>

                            <div class="space-y-3">
                                @forelse ($autoSavingsRules as $rule)
                                    <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">{{ $rule->subAccount->name }}</div>
                                                <div class="mt-1 text-sm text-slate-500">{{ ucfirst($rule->frequency) }} | {{ ucfirst($rule->type) }} {{ number_format((float) $rule->value, 2) }}{{ $rule->type === 'percentage' ? '%' : '' }}</div>
                                                <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-400">{{ $rule->next_run_at ? 'Next run ' . $rule->next_run_at->format('M d, Y h:i A') : 'Runs on deposit' }}</div>
                                            </div>
                                            <form method="POST" action="{{ route('automation.rules.destroy', $rule) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-6 text-sm text-slate-500">No auto-savings rules yet.</div>
                                @endforelse
                            </div>
                        </x-card>

                        <x-card title="Scheduled Transactions" subtitle="Future deposits and transfers managed automatically">
                            <x-slot:actions>
                                <button type="button" class="btn-primary" @click="openModal('scheduled-transaction')">Schedule</button>
                            </x-slot:actions>

                            <div class="space-y-3">
                                @forelse ($scheduledTransactions as $scheduledTransaction)
                                    <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">{{ ucfirst($scheduledTransaction->type) }} ${{ number_format((float) $scheduledTransaction->amount, 2) }}</div>
                                                <div class="mt-1 text-sm text-slate-500">
                                                    {{ ucfirst($scheduledTransaction->frequency) }}
                                                    @if ($scheduledTransaction->destinationSubAccount)
                                                        -> {{ $scheduledTransaction->destinationSubAccount->name }}
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-400">Next run {{ $scheduledTransaction->next_run_at->format('M d, Y h:i A') }}</div>
                                                @if ($scheduledTransaction->description)
                                                    <div class="mt-1 text-xs text-slate-500">{{ $scheduledTransaction->description }}</div>
                                                @endif
                                            </div>
                                            <form method="POST" action="{{ route('automation.scheduled.destroy', $scheduledTransaction) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-6 text-sm text-slate-500">No scheduled transactions yet.</div>
                                @endforelse
                            </div>
                        </x-card>
                    </section>
                </div>
            </main>
            <aside class="fin-right">
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">My Card</div>
                            <div class="mt-1 text-xl font-semibold text-slate-950">{{ $user->name }}</div>
                        </div>
                        <button type="button" class="rounded-2xl border border-stone-200 bg-white px-3 py-2 text-slate-600 shadow-sm transition hover:bg-stone-50">...</button>
                    </div>

                    <div class="relative overflow-hidden rounded-[1.75rem] p-5 text-white shadow-[0_18px_48px_rgba(15,23,42,0.18)] dark-card-preview">
                        <div class="flex items-start justify-between">
                            <div class="rounded-full border border-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/80">COINGROW</div>
                            <div class="text-sm text-white/70">Debit</div>
                        </div>
                        <div class="mt-10 text-xl font-semibold tracking-[0.3em]">**** **** **** 1234</div>
                        <div class="mt-8 flex items-end justify-between">
                            <div>
                                <div class="text-xs uppercase tracking-[0.18em] text-white/60">Card Holder</div>
                                <div class="mt-2 text-sm font-medium">{{ strtoupper($user->name) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/60">Expiry</div>
                                <div class="mt-2 text-sm font-medium">09/28</div>
                            </div>
                        </div>
                    </div>

                    <x-card padding="p-5" title="Current Account Balance" subtitle="Main account funds available now">
                        <x-slot:actions>
                            <button type="button" class="btn-primary !px-3 !py-2" @click="openModal('main-deposit')">Add Card</button>
                        </x-slot:actions>
                        <div class="text-3xl font-semibold tracking-tight text-slate-950">${{ number_format((float) $account->balance, 2) }}</div>
                    </x-card>

                    <x-card padding="p-5" title="Virtual Account" subtitle="Use this account to deposit money into COINGROW">
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Account Name</div>
                                <div class="mt-2 text-base font-semibold text-slate-950">{{ $account->account_name }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Account Number</div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <div class="text-xl font-semibold tracking-[0.24em] text-slate-950">{{ $account->account_number }}</div>
                                    <button
                                        type="button"
                                        class="btn-secondary !px-3 !py-2"
                                        @click="copyAccountNumber('{{ $account->account_number }}')"
                                    >
                                        Copy
                                    </button>
                                </div>
                            </div>
                            <div class="rounded-[1.15rem] border border-stone-200 bg-[#faf8f4] p-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Bank / Provider</div>
                                <div class="mt-2 text-sm font-medium text-slate-900">{{ $account->bank_name ?? 'COINGROW DIGITAL' }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ $account->provider ?? 'internal' }}</div>
                            </div>
                            <button type="button" class="btn-primary w-full" @click="openModal('main-deposit')">Fund Account</button>
                        </div>
                    </x-card>

                    <x-card padding="p-5" title="Transactions" subtitle="Most recent money movement">
                        <x-slot:actions>
                            <a href="#transactions" class="soft-link">View all</a>
                        </x-slot:actions>

                        <div class="space-y-3">
                            @forelse ($recentTransactions as $transaction)
                                @php
                                    $incoming = in_array($transaction->type, ['deposit', 'auto_split', 'scheduled_deposit'], true);
                                @endphp
                                <x-transaction-item
                                    :name="$transaction->subAccount?->name ?? 'Main Account'"
                                    :description="$transaction->description"
                                    :amount="'$' . number_format((float) $transaction->amount, 2)"
                                    :direction="$incoming ? 'incoming' : 'outgoing'"
                                    :time="$transaction->created_at->diffForHumans()"
                                />
                            @empty
                                <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-5 text-sm text-slate-500">
                                    No recent transactions yet.
                                </div>
                            @endforelse
                        </div>
                    </x-card>

                    <x-card padding="p-5" title="System Alerts" subtitle="Unread notifications and milestone updates">
                        <x-slot:actions>
                            @if ($unreadNotifications > 0)
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $unreadNotifications }} new</span>
                            @endif
                        </x-slot:actions>

                        <div class="space-y-3">
                            @forelse ($notifications->take(4) as $notification)
                                <div class="rounded-[1.15rem] border border-stone-200 bg-[#faf8f4] p-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ data_get($notification->data, 'title') }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ data_get($notification->data, 'message') }}</div>
                                    <div class="mt-2 text-xs uppercase tracking-[0.14em] text-slate-400">{{ $notification->created_at->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-5 text-sm text-slate-500">
                                    No notifications yet.
                                </div>
                            @endforelse
                        </div>
                    </x-card>
                </div>
            </aside>
        </div>

        <div x-cloak x-show="modal === 'main-deposit'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Deposit to main account</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('main-account.deposit') }}" class="space-y-4">
                    @csrf
                    <div><label class="form-label">Amount</label><input type="number" name="amount" step="0.01" min="0.01" required class="form-input"></div>
                    <div><label class="form-label">Category</label><select name="category" class="form-input">@foreach ($transactionCategories as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div>
                    <div><label class="form-label">Note</label><textarea name="note" rows="3" class="form-input"></textarea></div>
                    <div><label class="form-label">Tags</label><input type="text" name="tags" placeholder="salary, monthly" class="form-input"></div>
                    <button type="submit" class="btn-primary w-full">Process deposit</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'main-withdraw'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Withdraw from main account</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('main-account.withdraw') }}" class="space-y-4">
                    @csrf
                    <div><label class="form-label">Amount</label><input type="number" name="amount" step="0.01" min="0.01" required class="form-input"></div>
                    <div><label class="form-label">Category</label><select name="category" class="form-input">@foreach ($transactionCategories as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div>
                    <div><label class="form-label">Note</label><textarea name="note" rows="3" class="form-input"></textarea></div>
                    <div><label class="form-label">Tags</label><input type="text" name="tags" placeholder="groceries, urgent" class="form-input"></div>
                    <button type="submit" class="btn-primary w-full">Process withdrawal</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'create-wallet'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Create savings wallet</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('sub-accounts.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="form-label">Wallet name</label><input type="text" name="name" required class="form-input"></div>
                    <div><label class="form-label">Target amount</label><input type="number" name="target" step="0.01" min="0" class="form-input"></div>
                    <div><label class="form-label">Split percentage</label><input type="number" name="split_percentage" step="0.01" min="0" max="100" class="form-input"></div>
                    <label class="flex items-center gap-3 text-sm text-slate-600">
                        <input type="checkbox" name="locked" value="1" class="h-4 w-4 rounded border-stone-300 bg-white text-slate-950 focus:ring-slate-300">
                        Start locked
                    </label>
                    <button type="submit" class="btn-primary w-full">Create wallet</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'sub-action'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title" x-text="subActionLabel"></h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" :action="subActionUrl" class="space-y-4">
                    @csrf
                    <div><label class="form-label">Amount</label><input type="number" name="amount" step="0.01" min="0.01" required class="form-input"></div>
                    <div><label class="form-label">Category</label><select name="category" class="form-input">@foreach ($transactionCategories as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div>
                    <div><label class="form-label">Note</label><textarea name="note" rows="3" class="form-input"></textarea></div>
                    <div><label class="form-label">Tags</label><input type="text" name="tags" placeholder="goal, urgent" class="form-input"></div>
                    <button type="submit" class="btn-primary w-full" x-text="subActionButton"></button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'transfer'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title" x-text="transferLabel"></h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" :action="transferUrl" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Destination wallet</label>
                        <select name="destination_sub_account_id" class="form-input" required>
                            <option value="">Select destination</option>
                            @foreach ($subAccounts as $subAccount)
                                <option value="{{ $subAccount->id }}">{{ $subAccount->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Amount</label><input type="number" name="amount" step="0.01" min="0.01" required class="form-input"></div>
                    <div><label class="form-label">Category</label><select name="category" class="form-input"><option value="transfer">Transfer</option><option value="savings">Savings</option><option value="other">Other</option></select></div>
                    <div><label class="form-label">Note</label><textarea name="note" rows="3" class="form-input"></textarea></div>
                    <div><label class="form-label">Tags</label><input type="text" name="tags" placeholder="rebalance, rent" class="form-input"></div>
                    <button type="submit" class="btn-primary w-full">Move funds</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'auto-savings'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Create auto-savings rule</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('automation.rules.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Target wallet</label>
                        <select name="sub_account_id" class="form-input" required>
                            @foreach ($subAccounts as $subAccount)
                                <option value="{{ $subAccount->id }}">{{ $subAccount->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Rule type</label>
                        <select name="type" class="form-input" required>
                            @foreach ($automationOptions['ruleTypes'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Value</label><input type="number" name="value" step="0.01" min="0.01" required class="form-input"></div>
                    <div>
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-input" required>
                            @foreach ($automationOptions['ruleFrequencies'] as $frequency)
                                <option value="{{ $frequency }}">{{ str_replace('_', ' ', ucfirst($frequency)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full">Save rule</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'scheduled-transaction'" class="modal-shell">
            <div class="modal-card modal-card-wide">
                <div class="modal-head">
                    <h3 class="modal-title">Create scheduled transaction</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('automation.scheduled.store') }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label class="form-label">Type</label>
                        <select name="type" class="form-input" required>
                            @foreach ($automationOptions['scheduledTypes'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-input" required>
                            @foreach ($automationOptions['scheduledFrequencies'] as $frequency)
                                <option value="{{ $frequency }}">{{ ucfirst($frequency) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Source wallet</label>
                        <select name="source_sub_account_id" class="form-input">
                            <option value="">Main account</option>
                            @foreach ($subAccounts as $subAccount)
                                <option value="{{ $subAccount->id }}">{{ $subAccount->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Destination wallet</label>
                        <select name="destination_sub_account_id" class="form-input">
                            <option value="">Select destination</option>
                            @foreach ($subAccounts as $subAccount)
                                <option value="{{ $subAccount->id }}">{{ $subAccount->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Amount</label><input type="number" name="amount" step="0.01" min="0.01" required class="form-input"></div>
                    <div><label class="form-label">Description</label><input type="text" name="description" class="form-input"></div>
                    <div class="md:col-span-2">
                        <button type="submit" class="btn-primary w-full">Schedule transaction</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'split-settings'" class="modal-shell">
            <div class="modal-card modal-card-wide">
                <div class="modal-head">
                    <h3 class="modal-title">Payment split settings</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>

                <form method="POST" action="{{ route('payment-splits.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3">
                        @foreach ($subAccounts as $subAccount)
                            <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $subAccount->name }}</div>
                                        <div class="text-sm text-slate-500">Target {{ $subAccount->target ? '$' . number_format((float) $subAccount->target, 2) : 'not set' }}</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="splits[{{ $loop->index }}][sub_account_id]" value="{{ $subAccount->id }}">
                                        <input type="number" step="0.01" min="0" max="100" name="splits[{{ $loop->index }}][percentage]" value="{{ number_format((float) ($subAccount->paymentSplit?->percentage ?? 0), 2, '.', '') }}" class="form-input w-28" x-model.number="splits[{{ $loop->index }}]">
                                        <span class="text-sm text-slate-500">%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        Preview total: <span class="font-semibold" x-text="splitPreview.toFixed(2)"></span>%
                        <span class="ml-3">Main account keeps <span class="font-semibold" x-text="(100 - splitPreview).toFixed(2)"></span>%.</span>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="btn-primary" :disabled="splitPreview > 100">Save split rules</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('payment-splits.clear') }}" class="mt-3" onsubmit="return confirm('Clear all split rules?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Clear all</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'notifications'" class="modal-shell">
            <div class="modal-card modal-card-wide">
                <div class="modal-head">
                    <h3 class="modal-title">Notifications center</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>

                <div class="space-y-3">
                    @forelse ($notifications as $notification)
                        <div class="rounded-[1.25rem] border border-stone-200 bg-[#faf8f4] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-slate-900">{{ data_get($notification->data, 'title') }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ data_get($notification->data, 'message') }}</div>
                                </div>
                                <div class="text-xs uppercase tracking-[0.14em] text-slate-400">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.25rem] border border-dashed border-stone-300 bg-[#faf8f4] p-6 text-sm text-slate-500">No notifications yet.</div>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('notifications.read-all') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn-secondary">Mark all as read</button>
                </form>
            </div>
        </div>

        <div x-cloak x-show="modal === 'change-password'" class="modal-shell">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Change password</h3>
                    <button type="button" class="modal-close" @click="closeModal()">Close</button>
                </div>
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div><label class="form-label">Current password</label><input type="password" name="current_password" required class="form-input"></div>
                    <div><label class="form-label">New password</label><input type="password" name="password" required class="form-input"></div>
                    <div><label class="form-label">Confirm new password</label><input type="password" name="password_confirmation" required class="form-input"></div>
                    <button type="submit" class="btn-primary w-full">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function dashboardApp() {
            return {
                modal: null,
                chartRange: 'monthly',
                subActionUrl: '',
                subActionLabel: '',
                subActionButton: 'Continue',
                transferUrl: '',
                transferLabel: '',
                splits: @json($subAccounts->map(fn ($subAccount) => (float) ($subAccount->paymentSplit?->percentage ?? 0))->values()),
                get splitPreview() {
                    return this.splits.reduce((total, item) => total + (Number(item) || 0), 0);
                },
                openModal(name) {
                    this.modal = name;
                },
                closeModal() {
                    this.modal = null;
                },
                openSubAction(type, url, walletName) {
                    this.subActionUrl = url;
                    this.subActionLabel = `${type === 'deposit' ? 'Deposit to' : 'Withdraw from'} ${walletName}`;
                    this.subActionButton = type === 'deposit' ? 'Confirm deposit' : 'Confirm withdrawal';
                    this.modal = 'sub-action';
                },
                openTransferModal(url, walletName) {
                    this.transferUrl = url;
                    this.transferLabel = `Transfer from ${walletName}`;
                    this.modal = 'transfer';
                },
                async copyAccountNumber(accountNumber) {
                    if (! navigator.clipboard) {
                        return;
                    }

                    await navigator.clipboard.writeText(accountNumber);
                },
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('moneyFlowChart');

            if (!ctx) {
                return;
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($analytics['incomeExpense']['labels']),
                    datasets: [
                        {
                            label: 'Income',
                            data: @json($analytics['incomeExpense']['income']),
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.08)',
                            fill: false,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 4,
                        },
                        {
                            label: 'Outcome',
                            data: @json($analytics['incomeExpense']['expenses']),
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.08)',
                            fill: false,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#475569',
                                usePointStyle: true,
                                boxWidth: 8,
                                boxHeight: 8,
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(148, 163, 184, 0.15)' },
                            ticks: { color: '#64748b' }
                        },
                        y: {
                            grid: { color: 'rgba(148, 163, 184, 0.15)' },
                            ticks: { color: '#64748b' }
                        }
                    }
                }
            });
        });
    </script>
@endsection

@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div x-data="dashboardApp()" class="space-y-6">
        <header class="flex flex-col gap-4 rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.35em] text-cyan-200/75">COINGROW Dashboard</p>
                <h1 class="mt-3 text-3xl font-semibold text-white sm:text-4xl">Welcome back, {{ $user->name }}</h1>
                <p class="mt-2 text-sm text-slate-300">Track wallet health, move funds instantly, and monitor spending trends from one financial control center.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="btn-secondary" @click="openModal('notifications')">
                    Notifications
                    @if ($unreadNotifications > 0)
                        <span class="ml-2 rounded-full bg-cyan-300 px-2 py-0.5 text-xs font-semibold text-slate-950">{{ $unreadNotifications }}</span>
                    @endif
                </button>
                <button type="button" class="btn-secondary" @click="openModal('change-password')">Security</button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Log out</button>
                </form>
            </div>
        </header>

        <section class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
            <div class="rounded-[2rem] border border-white/10 bg-slate-950/75 p-6 shadow-2xl shadow-cyan-950/20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Main balance</p>
                        <h2 class="mt-3 text-4xl font-semibold text-white">${{ number_format((float) $account->balance, 2) }}</h2>
                        <p class="mt-2 text-sm text-slate-400">Primary balance after split distribution and wallet transfers.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="btn-primary" @click="openModal('main-deposit')">Deposit</button>
                        <button type="button" class="btn-secondary" @click="openModal('main-withdraw')">Withdraw</button>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-4">
                    <div class="glass-card">
                        <div class="metric-label">Wallets</div>
                        <div class="metric-value">{{ $subAccounts->count() }}</div>
                        <p class="metric-copy">Savings spaces linked to your main account.</p>
                    </div>
                    <div class="glass-card">
                        <div class="metric-label">Split total</div>
                        <div class="metric-value">{{ number_format($splitTotal, 2) }}%</div>
                        <p class="metric-copy">{{ number_format(100 - $splitTotal, 2) }}% stays in main by default.</p>
                    </div>
                    <div class="glass-card">
                        <div class="metric-label">Locked wallets</div>
                        <div class="metric-value">{{ $subAccounts->where('locked', true)->count() }}</div>
                        <p class="metric-copy">Protected until unlocked or target completion.</p>
                    </div>
                    <div class="glass-card">
                        <div class="metric-label">Unread alerts</div>
                        <div class="metric-value">{{ $unreadNotifications }}</div>
                        <p class="metric-copy">Low balance, funding, and milestone updates.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Split engine</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Deposit automation</h2>
                    </div>
                    <button type="button" class="btn-secondary" @click="openModal('split-settings')">Manage</button>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($subAccounts as $subAccount)
                        @php $percentage = (float) ($subAccount->paymentSplit?->percentage ?? 0); @endphp
                        <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <div class="font-medium text-white">{{ $subAccount->name }}</div>
                                    <div class="text-sm text-slate-400">
                                        {{ $percentage > 0 ? number_format($percentage, 2) . '% of main deposits' : 'Not included in split' }}
                                    </div>
                                </div>
                                <div class="text-sm text-slate-300">${{ number_format((float) $subAccount->balance, 2) }}</div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-emerald-300" style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">
                            Create your first savings wallet to start splitting deposits automatically.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur xl:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Analytics</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Income vs expenses</h2>
                    </div>
                    <div class="text-sm text-slate-400">Last 6 months</div>
                </div>
                <div class="mt-6 h-80 rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-4">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Distribution</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">Wallet allocation</h2>
                    <div class="mt-6 h-72 rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-4">
                        <canvas id="walletDistributionChart"></canvas>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Growth</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">Savings momentum</h2>
                    <div class="mt-6 h-72 rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-4">
                        <canvas id="savingsGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Phase 2</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Auto-savings rules</h2>
                    </div>
                    <button type="button" class="btn-primary" @click="openModal('auto-savings')">Add rule</button>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($autoSavingsRules as $rule)
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-white">{{ $rule->subAccount->name }}</div>
                                    <div class="mt-1 text-sm text-slate-400">{{ ucfirst($rule->frequency) }} | {{ ucfirst($rule->type) }} {{ number_format((float) $rule->value, 2) }}{{ $rule->type === 'percentage' ? '%' : '' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $rule->next_run_at ? 'Next run ' . $rule->next_run_at->format('M d, Y h:i A') : 'Runs whenever a deposit lands' }}</div>
                                </div>
                                <form method="POST" action="{{ route('automation.rules.destroy', $rule) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">No auto-savings rules yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Automation queue</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Scheduled transactions</h2>
                    </div>
                    <button type="button" class="btn-primary" @click="openModal('scheduled-transaction')">Schedule</button>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($scheduledTransactions as $scheduledTransaction)
                        <div class="rounded-2xl border border-white/10 bg-slate-950/70 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-white">{{ ucfirst($scheduledTransaction->type) }} {{ number_format((float) $scheduledTransaction->amount, 2) }}</div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        {{ ucfirst($scheduledTransaction->frequency) }}
                                        @if ($scheduledTransaction->destinationSubAccount)
                                            -> {{ $scheduledTransaction->destinationSubAccount->name }}
                                        @endif
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">Next run {{ $scheduledTransaction->next_run_at->format('M d, Y h:i A') }}</div>
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
                        <div class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">No scheduled transactions yet.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr,0.95fr]">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Savings wallets</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Sub-accounts</h2>
                    </div>
                    <button type="button" class="btn-primary" @click="openModal('create-wallet')">Create wallet</button>
                </div>

                <div class="mt-6 grid gap-4">
                    @forelse ($subAccounts as $subAccount)
                        <article class="rounded-[1.5rem] border border-white/10 bg-slate-950/70 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-lg font-semibold text-white">{{ $subAccount->name }}</h3>
                                        @if ($subAccount->locked)
                                            <span class="rounded-full border border-amber-300/20 bg-amber-400/10 px-3 py-1 text-xs font-medium text-amber-100">Locked</span>
                                        @else
                                            <span class="rounded-full border border-emerald-300/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-100">Available</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 text-3xl font-semibold text-white">${{ number_format((float) $subAccount->balance, 2) }}</div>
                                    <div class="mt-2 text-sm text-slate-400">
                                        Target: {{ $subAccount->target ? '$' . number_format((float) $subAccount->target, 2) : 'No target set' }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        Remaining: {{ $subAccount->target ? '$' . number_format($subAccount->remainingToTarget(), 2) : 'Not applicable' }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $subAccount->estimatedCompletionLabel() }}</div>
                                </div>

                                <div class="flex flex-wrap gap-3 lg:justify-end">
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

                            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                                <div class="mini-stat">
                                    <span class="mini-stat-label">Target progress</span>
                                    <span class="mini-stat-value">{{ number_format($subAccount->targetProgress(), 1) }}%</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="mini-stat-label">Split share</span>
                                    <span class="mini-stat-value">{{ number_format((float) ($subAccount->paymentSplit?->percentage ?? 0), 2) }}%</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="mini-stat-label">Status rule</span>
                                    <span class="mini-stat-value">{{ $subAccount->target ? 'Auto unlock at target' : 'Manual lock control' }}</span>
                                </div>
                            </div>

                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-300 to-cyan-400" style="width: {{ $subAccount->targetProgress() }}%"></div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-white/10 p-8 text-center text-sm text-slate-400">
                            No sub-accounts yet. Create one to start saving toward a goal.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-slate-950/75 p-6 shadow-2xl shadow-cyan-950/20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Audit trail</p>
                        <h2 class="mt-2 text-2xl font-semibold text-white">Transaction history</h2>
                    </div>

                    <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
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

                <div class="mt-6 space-y-3">
                    @forelse ($transactions as $transaction)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-sm font-medium uppercase tracking-[0.2em] text-cyan-200/80">{{ str_replace('_', ' ', $transaction->type) }}</div>
                                        @if ($transaction->category)
                                            <span class="rounded-full border border-cyan-300/20 bg-cyan-300/10 px-2 py-1 text-[11px] uppercase tracking-[0.2em] text-cyan-100">{{ $transaction->category }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-1 text-sm text-slate-200">{{ $transaction->description }}</div>
                                    @if ($transaction->note)
                                        <div class="mt-1 text-sm text-slate-400">{{ $transaction->note }}</div>
                                    @endif
                                    @if (! empty($transaction->tags))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($transaction->tags as $tag)
                                                <span class="rounded-full border border-white/10 px-2 py-1 text-[11px] text-slate-300">#{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="mt-2 text-xs text-slate-500">
                                        {{ $transaction->subAccount?->name ?? 'Main account' }}
                                        @if ($transaction->relatedSubAccount)
                                            -> {{ $transaction->relatedSubAccount->name }}
                                        @endif
                                        | {{ $transaction->created_at->format('M d, Y h:i A') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-base font-semibold text-white">${{ number_format((float) $transaction->amount, 2) }}</div>
                                    <div class="mt-1 text-xs text-slate-400">Balance after: ${{ number_format((float) $transaction->balance_after, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">
                            Transaction activity will appear here as soon as you start using the platform.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            </div>
        </section>

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
                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="locked" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-cyan-400 focus:ring-cyan-300">
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
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="font-medium text-white">{{ $subAccount->name }}</div>
                                        <div class="text-sm text-slate-400">Target {{ $subAccount->target ? '$' . number_format((float) $subAccount->target, 2) : 'not set' }}</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" name="splits[{{ $loop->index }}][sub_account_id]" value="{{ $subAccount->id }}">
                                        <input type="number" step="0.01" min="0" max="100" name="splits[{{ $loop->index }}][percentage]" value="{{ number_format((float) ($subAccount->paymentSplit?->percentage ?? 0), 2, '.', '') }}" class="form-input w-28" x-model.number="splits[{{ $loop->index }}]">
                                        <span class="text-sm text-slate-300">%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-4 text-sm text-cyan-100">
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
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-white">{{ data_get($notification->data, 'title') }}</div>
                                    <div class="mt-1 text-sm text-slate-300">{{ data_get($notification->data, 'message') }}</div>
                                </div>
                                <div class="text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">No notifications yet.</div>
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
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            const textColor = '#cbd5e1';
            const gridColor = 'rgba(148, 163, 184, 0.18)';

            new Chart(document.getElementById('incomeExpenseChart'), {
                type: 'bar',
                data: {
                    labels: @json($analytics['incomeExpense']['labels']),
                    datasets: [
                        { label: 'Income', data: @json($analytics['incomeExpense']['income']), backgroundColor: 'rgba(34, 211, 238, 0.75)', borderRadius: 12 },
                        { label: 'Expenses', data: @json($analytics['incomeExpense']['expenses']), backgroundColor: 'rgba(251, 113, 133, 0.75)', borderRadius: 12 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: textColor } } },
                    scales: {
                        x: { ticks: { color: textColor }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor }, grid: { color: gridColor } }
                    }
                }
            });

            new Chart(document.getElementById('walletDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($analytics['walletDistribution']['labels']),
                    datasets: [{ data: @json($analytics['walletDistribution']['values']), backgroundColor: ['#22d3ee', '#34d399', '#f59e0b', '#fb7185', '#a78bfa', '#60a5fa'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } } }
            });

            new Chart(document.getElementById('savingsGrowthChart'), {
                type: 'line',
                data: {
                    labels: @json($analytics['savingsGrowth']['labels']),
                    datasets: [{ label: 'Savings growth', data: @json($analytics['savingsGrowth']['values']), fill: true, borderColor: '#34d399', backgroundColor: 'rgba(52, 211, 153, 0.18)', tension: 0.35 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: textColor } } },
                    scales: {
                        x: { ticks: { color: textColor }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor }, grid: { color: gridColor } }
                    }
                }
            });
        });
    </script>
@endsection

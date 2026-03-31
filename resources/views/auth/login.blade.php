@extends('layouts.app')

@section('content')
    <div class="mx-auto flex w-full max-w-6xl flex-1 items-center">
        <div class="grid w-full gap-8 lg:grid-cols-[1.2fr,0.8fr]">
            <section class="rounded-[2rem] border border-white/10 bg-white/5 p-8 backdrop-blur xl:p-12">
                <p class="text-sm uppercase tracking-[0.4em] text-cyan-200/80">COINGROW</p>
                <h1 class="mt-4 max-w-xl text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    A savings system built to feel like a real digital bank.
                </h1>
                <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300">
                    Secure authentication, split deposits, locked savings targets, and a full audit trail in one production-ready fintech workspace.
                </p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="glass-card">
                        <div class="metric-label">Split deposits</div>
                        <div class="metric-value">100%</div>
                        <p class="metric-copy">Route income automatically across wallets without manual juggling.</p>
                    </div>
                    <div class="glass-card">
                        <div class="metric-label">Secure lock rules</div>
                        <div class="metric-value">Atomic</div>
                        <p class="metric-copy">Protected withdrawals and target-based unlock automation.</p>
                    </div>
                    <div class="glass-card">
                        <div class="metric-label">Full visibility</div>
                        <div class="metric-value">24/7</div>
                        <p class="metric-copy">Every action logged with balances, timestamps, and descriptions.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-slate-950/75 p-8 shadow-2xl shadow-cyan-950/30 backdrop-blur xl:p-10">
                <h2 class="text-2xl font-semibold text-white">Sign in</h2>
                <p class="mt-2 text-sm text-slate-400">Use your COINGROW username and password to access your dashboard.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="username" class="form-label">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required class="form-input">
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input id="password" name="password" type="password" required class="form-input">
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-300">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-cyan-400 focus:ring-cyan-300">
                        Keep me signed in
                    </label>

                    <button type="submit" class="btn-primary w-full">Log in</button>
                </form>

                <p class="mt-6 text-sm text-slate-400">
                    New to COINGROW?
                    <a href="{{ route('register') }}" class="font-medium text-cyan-300 hover:text-cyan-200">Create your account</a>
                </p>
            </section>
        </div>
    </div>
@endsection

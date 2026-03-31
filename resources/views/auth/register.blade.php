@extends('layouts.app')

@section('content')
    <div class="mx-auto flex w-full max-w-xl flex-1 items-center">
        <section class="w-full rounded-[2rem] border border-white/10 bg-slate-950/80 p-8 shadow-2xl shadow-cyan-950/30 backdrop-blur">
            <p class="text-sm uppercase tracking-[0.4em] text-cyan-200/80">Open Account</p>
            <h1 class="mt-4 text-3xl font-semibold text-white">Create your COINGROW profile</h1>
            <p class="mt-2 text-sm text-slate-400">Your main account will be provisioned automatically after registration.</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="username" class="form-label">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required class="form-input">
                </div>

                <div>
                    <label for="name" class="form-label">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required class="form-input">
                </div>

                <div>
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" required class="form-input">
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="form-input">
                </div>

                <button type="submit" class="btn-primary w-full">Create account</button>
            </form>

            <p class="mt-6 text-sm text-slate-400">
                Already registered?
                <a href="{{ route('login') }}" class="font-medium text-cyan-300 hover:text-cyan-200">Sign in instead</a>
            </p>
        </section>
    </div>
@endsection

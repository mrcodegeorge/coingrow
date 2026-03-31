@extends('layouts.app')

@section('content')
    <div class="mx-auto flex w-full max-w-xl flex-1 items-center">
        <section class="w-full rounded-[2rem] border border-white/10 bg-slate-950/80 p-8 shadow-2xl shadow-cyan-950/30 backdrop-blur">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.4em] text-cyan-200/80">Security</p>
                    <h1 class="mt-3 text-3xl font-semibold text-white">Change password</h1>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-secondary">Back</a>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="form-label">Current password</label>
                    <input id="current_password" name="current_password" type="password" required class="form-input">
                </div>

                <div>
                    <label for="password" class="form-label">New password</label>
                    <input id="password" name="password" type="password" required class="form-input">
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="form-input">
                </div>

                <button type="submit" class="btn-primary w-full">Update password</button>
            </form>
        </section>
    </div>
@endsection

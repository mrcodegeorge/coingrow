<?php

namespace App\Http\Controllers;

use App\Services\TransactionLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        protected TransactionLoggerService $logger
    ) {
    }

    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $this->logger->log($user->account, 'password_changed', 0, (float) $user->account->balance, 'User changed password.');

        return back()->with('status', 'Password updated successfully.');
    }
}

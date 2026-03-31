<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BankingService;
use App\Services\TransactionLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected BankingService $bankingService,
        protected TransactionLoggerService $logger
    ) {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'The provided credentials do not match our records.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $account = $request->user()->account;
        $this->logger->log($account, 'login', 0, (float) $account->balance, 'User logged in.');

        return redirect()->route('dashboard')->with('status', 'Welcome back to COINGROW.');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'name' => $validated['name'],
                'password' => $validated['password'],
            ]);

            $account = $this->bankingService->createPrimaryAccountForUser($user->id);
            $this->logger->log($account, 'account_created', 0, 0, 'Primary account created during registration.');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your COINGROW account is ready.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user?->account) {
            $this->logger->log($user->account, 'logout', 0, (float) $user->account->balance, 'User logged out.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}

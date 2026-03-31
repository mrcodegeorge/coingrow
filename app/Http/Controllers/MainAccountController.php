<?php

namespace App\Http\Controllers;

use App\Services\BankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class MainAccountController extends Controller
{
    public function __construct(
        protected BankingService $bankingService
    ) {
    }

    public function deposit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->bankingService->depositToMain(
                $request->user()->account,
                (float) $validated['amount']
            );

            return back()->with('status', 'Deposit completed and payment splits were applied.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['main_deposit' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['main_deposit' => 'Deposit could not be processed.']);
        }
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->bankingService->withdrawFromMain(
                $request->user()->account,
                (float) $validated['amount']
            );

            return back()->with('status', 'Funds withdrawn from your main account.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['main_withdraw' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['main_withdraw' => 'Withdrawal could not be processed.']);
        }
    }
}

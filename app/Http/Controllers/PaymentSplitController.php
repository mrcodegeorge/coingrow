<?php

namespace App\Http\Controllers;

use App\Services\BankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PaymentSplitController extends Controller
{
    public function __construct(
        protected BankingService $bankingService
    ) {
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'splits' => ['array'],
            'splits.*.sub_account_id' => ['required', 'integer'],
            'splits.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            $this->bankingService->replacePaymentSplits(
                $request->user()->account,
                $validated['splits'] ?? []
            );

            return back()->with('status', 'Payment split rules updated.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['splits' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['splits' => 'Payment splits could not be saved.']);
        }
    }

    public function clear(Request $request): RedirectResponse
    {
        try {
            $this->bankingService->clearPaymentSplits($request->user()->account);

            return back()->with('status', 'All payment split rules were cleared.');
        } catch (Throwable) {
            return back()->withErrors(['splits' => 'Payment splits could not be cleared.']);
        }
    }
}

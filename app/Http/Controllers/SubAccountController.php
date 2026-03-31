<?php

namespace App\Http\Controllers;

use App\Models\SubAccount;
use App\Services\BankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class SubAccountController extends Controller
{
    public function __construct(
        protected BankingService $bankingService
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'target' => ['nullable', 'numeric', 'min:0'],
            'locked' => ['nullable', 'boolean'],
            'split_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            $this->bankingService->createSubAccount(
                $request->user()->account,
                $validated['name'],
                isset($validated['target']) ? (float) $validated['target'] : null,
                $request->boolean('locked'),
                isset($validated['split_percentage']) && $validated['split_percentage'] !== null
                    ? (float) $validated['split_percentage']
                    : null
            );

            return back()->with('status', 'Sub-account created successfully.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['sub_account' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['sub_account' => 'Sub-account could not be created.']);
        }
    }

    public function deposit(Request $request, SubAccount $subAccount): RedirectResponse
    {
        $this->authorizeSubAccount($request, $subAccount);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->bankingService->depositToSubAccount($subAccount, (float) $validated['amount']);

            return back()->with('status', "Funds added to {$subAccount->name}.");
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(["sub_deposit_{$subAccount->id}" => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(["sub_deposit_{$subAccount->id}" => 'Deposit could not be processed.']);
        }
    }

    public function withdraw(Request $request, SubAccount $subAccount): RedirectResponse
    {
        $this->authorizeSubAccount($request, $subAccount);
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->bankingService->withdrawFromSubAccount($subAccount, (float) $validated['amount']);

            return back()->with('status', "Funds withdrawn from {$subAccount->name}.");
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(["sub_withdraw_{$subAccount->id}" => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(["sub_withdraw_{$subAccount->id}" => 'Withdrawal could not be processed.']);
        }
    }

    public function updateLock(Request $request, SubAccount $subAccount): RedirectResponse
    {
        $this->authorizeSubAccount($request, $subAccount);
        $validated = $request->validate([
            'locked' => ['required', 'boolean'],
        ]);

        try {
            $this->bankingService->setLockState($subAccount, (bool) $validated['locked']);

            return back()->with('status', $validated['locked'] ? 'Sub-account locked.' : 'Sub-account unlocked.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(["sub_lock_{$subAccount->id}" => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(["sub_lock_{$subAccount->id}" => 'Lock state could not be changed.']);
        }
    }

    public function destroy(Request $request, SubAccount $subAccount): RedirectResponse
    {
        $this->authorizeSubAccount($request, $subAccount);

        try {
            $this->bankingService->deleteSubAccount($subAccount);

            return back()->with('status', 'Sub-account deleted.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(["sub_delete_{$subAccount->id}" => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(["sub_delete_{$subAccount->id}" => 'Sub-account could not be deleted.']);
        }
    }

    protected function authorizeSubAccount(Request $request, SubAccount $subAccount): void
    {
        abort_unless($subAccount->account_id === $request->user()->account->id, 404);
    }
}

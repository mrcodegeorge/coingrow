<?php

namespace App\Http\Controllers;

use App\Models\AutoSavingsRule;
use App\Models\ScheduledTransaction;
use App\Services\AutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class AutomationController extends Controller
{
    public function __construct(
        protected AutomationService $automationService
    ) {
    }

    public function storeAutoSavingsRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sub_account_id' => ['required', 'integer'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'in:daily,weekly,per_deposit'],
        ]);

        $request->user()->account->subAccounts()->findOrFail((int) $validated['sub_account_id']);

        try {
            $this->automationService->createAutoSavingsRule([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);

            return back()->with('status', 'Auto-savings rule created.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['automation' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['automation' => 'Auto-savings rule could not be created.']);
        }
    }

    public function storeScheduledTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:deposit,transfer'],
            'source_sub_account_id' => ['nullable', 'integer'],
            'destination_sub_account_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['source_sub_account_id'])) {
            $request->user()->account->subAccounts()->findOrFail((int) $validated['source_sub_account_id']);
        }

        if (! empty($validated['destination_sub_account_id'])) {
            $request->user()->account->subAccounts()->findOrFail((int) $validated['destination_sub_account_id']);
        }

        try {
            $this->automationService->createScheduledTransaction([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);

            return back()->with('status', 'Scheduled transaction created.');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['scheduled_transaction' => $exception->getMessage()]);
        } catch (Throwable) {
            return back()->withErrors(['scheduled_transaction' => 'Scheduled transaction could not be created.']);
        }
    }

    public function destroyAutoSavingsRule(Request $request, AutoSavingsRule $rule): RedirectResponse
    {
        abort_unless($rule->user_id === $request->user()->id, 404);
        $rule->delete();

        return back()->with('status', 'Auto-savings rule removed.');
    }

    public function destroyScheduledTransaction(Request $request, ScheduledTransaction $scheduledTransaction): RedirectResponse
    {
        abort_unless($scheduledTransaction->user_id === $request->user()->id, 404);
        $scheduledTransaction->delete();

        return back()->with('status', 'Scheduled transaction removed.');
    }
}

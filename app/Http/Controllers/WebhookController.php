<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\BankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        protected BankingService $bankingService
    ) {
    }

    public function handleDeposit(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'account_number' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['required', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::where('account_number', $validated['account_number'])->first();

        if (! $account) {
            return response()->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        if (Transaction::where('external_reference', $validated['reference'])->exists()) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Deposit already processed.',
            ], Response::HTTP_OK);
        }

        try {
            DB::transaction(function () use ($account, $validated) {
                $this->bankingService->depositToMain(
                    $account,
                    (float) $validated['amount'],
                    [
                        'category' => 'income',
                        'note' => 'Webhook deposit received.',
                        'tags' => ['webhook', $validated['provider'] ?? 'internal'],
                        'external_reference' => $validated['reference'],
                    ],
                    $validated['description'] ?? 'Webhook deposit received into virtual account.'
                );
            });
        } catch (Throwable $exception) {
            Log::error('Webhook deposit failed.', [
                'account_number' => $validated['account_number'],
                'reference' => $validated['reference'],
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'Deposit could not be processed'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'status' => 'success',
            'account_number' => $account->account_number,
            'reference' => $validated['reference'],
        ], Response::HTTP_OK);
    }

    protected function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.coingrow.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $signature = (string) $request->header('X-Coingrow-Signature');

        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}

<?php

namespace App\Services;

use App\Models\Account;
use App\Models\PaymentSplit;
use App\Models\SubAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankingService
{
    public function __construct(
        protected TransactionLoggerService $logger,
        protected UserNotificationService $notificationService
    ) {
    }

    public function createPrimaryAccountForUser(int $userId): Account
    {
        return Account::create([
            'user_id' => $userId,
            'balance' => 0,
        ]);
    }

    public function createSubAccount(
        Account $account,
        string $name,
        ?float $target = null,
        bool $locked = false,
        ?float $splitPercentage = null
    ): SubAccount {
        return DB::transaction(function () use ($account, $name, $target, $locked, $splitPercentage) {
            $subAccount = $account->subAccounts()->create([
                'name' => $name,
                'balance' => 0,
                'target' => $target,
                'locked' => $locked,
            ]);

            if ($splitPercentage !== null && $splitPercentage > 0) {
                $this->upsertPaymentSplit($account, $subAccount, $splitPercentage);
            }

            $this->logger->log(
                $account,
                'sub_account_created',
                0,
                (float) $account->balance,
                "Created sub-account {$subAccount->name}.",
                $subAccount
            );

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function depositToMain(Account $account, float $amount, array $context = [], string $description = 'Main account deposit.'): Account
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($account, $amount, $context, $description) {
            $account->refresh();
            $account->balance = (float) $account->balance + $amount;
            $account->save();

            $this->logger->log($account, 'deposit', $amount, (float) $account->balance, $description, null, $context);

            $splits = $account->subAccounts()
                ->with('paymentSplit')
                ->whereHas('paymentSplit')
                ->get();

            if ($splits->isNotEmpty()) {
                $this->applyPaymentSplits($account, $amount, $splits);
            }

            app(AutomationService::class)->applyPerDepositRules($account, $amount);

            DB::afterCommit(function () use ($account, $amount) {
                $this->notificationService->notify(
                    $account->user,
                    'Deposit received',
                    sprintf('%.2f was deposited into your main account.', $amount),
                    'success'
                );
            });

            return $account->fresh(['subAccounts.paymentSplit']);
        });
    }

    public function withdrawFromMain(Account $account, float $amount, array $context = [], string $description = 'Main account withdrawal.'): Account
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($account, $amount, $context, $description) {
            $account->refresh();

            if ((float) $account->balance < $amount) {
                throw new InvalidArgumentException('Insufficient main account balance.');
            }

            $account->balance = (float) $account->balance - $amount;
            $account->save();

            $this->logger->log($account, 'withdrawal', $amount, (float) $account->balance, $description, null, $context);

            if ((float) $account->balance <= 100) {
                DB::afterCommit(function () use ($account) {
                    $this->notificationService->notify(
                        $account->user,
                        'Low balance alert',
                        sprintf('Your main balance is now %.2f.', (float) $account->balance),
                        'warning'
                    );
                });
            }

            return $account->fresh();
        });
    }

    public function depositToSubAccount(SubAccount $subAccount, float $amount, array $context = [], string $description = 'Sub-account deposit.'): SubAccount
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($subAccount, $amount, $context, $description) {
            $subAccount->refresh();
            $subAccount->balance = (float) $subAccount->balance + $amount;
            $subAccount->save();

            $this->logger->log(
                $subAccount->account,
                'sub_account_deposit',
                $amount,
                (float) $subAccount->balance,
                $description,
                $subAccount,
                $context
            );

            $this->autoUnlockIfEligible($subAccount);

            DB::afterCommit(function () use ($subAccount, $amount) {
                $this->notificationService->notify(
                    $subAccount->account->user,
                    'Savings wallet funded',
                    sprintf('%.2f was added to %s.', $amount, $subAccount->name),
                    'success'
                );
            });

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function withdrawFromSubAccount(SubAccount $subAccount, float $amount, array $context = [], string $description = 'Sub-account withdrawal.'): SubAccount
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($subAccount, $amount, $context, $description) {
            $subAccount->refresh();

            if ($subAccount->locked) {
                throw new InvalidArgumentException('Locked sub-accounts cannot be withdrawn from.');
            }

            if ((float) $subAccount->balance < $amount) {
                throw new InvalidArgumentException('Insufficient sub-account balance.');
            }

            $subAccount->balance = (float) $subAccount->balance - $amount;
            $subAccount->save();

            $this->logger->log(
                $subAccount->account,
                'sub_account_withdrawal',
                $amount,
                (float) $subAccount->balance,
                $description,
                $subAccount,
                $context
            );

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function transferBetweenSubAccounts(
        SubAccount $fromSubAccount,
        SubAccount $toSubAccount,
        float $amount,
        array $context = []
    ): array {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($fromSubAccount, $toSubAccount, $amount, $context) {
            $fromSubAccount->refresh();
            $toSubAccount->refresh();

            if ($fromSubAccount->account_id !== $toSubAccount->account_id) {
                throw new InvalidArgumentException('Transfers are only allowed within your own sub-accounts.');
            }

            if ($fromSubAccount->id === $toSubAccount->id) {
                throw new InvalidArgumentException('Choose different source and destination wallets.');
            }

            if ($fromSubAccount->locked) {
                throw new InvalidArgumentException('Locked sub-accounts cannot transfer funds out.');
            }

            if ((float) $fromSubAccount->balance < $amount) {
                throw new InvalidArgumentException('Insufficient balance in the source sub-account.');
            }

            $fromSubAccount->balance = round((float) $fromSubAccount->balance - $amount, 2);
            $fromSubAccount->save();

            $this->logger->log(
                $fromSubAccount->account,
                'transfer',
                $amount,
                (float) $fromSubAccount->balance,
                "Transfer sent from {$fromSubAccount->name} to {$toSubAccount->name}.",
                $fromSubAccount,
                array_merge($context, [
                    'category' => $context['category'] ?? 'transfer',
                    'related_sub_account_id' => $toSubAccount->id,
                    'tags' => array_values(array_unique(array_merge($context['tags'] ?? [], ['transfer_out']))),
                ])
            );

            $this->logger->log(
                $toSubAccount->account,
                'transfer',
                $amount,
                (float) $toSubAccount->balance + $amount,
                "Transfer received from {$fromSubAccount->name} into {$toSubAccount->name}.",
                $toSubAccount,
                array_merge($context, [
                    'category' => $context['category'] ?? 'transfer',
                    'related_sub_account_id' => $fromSubAccount->id,
                    'tags' => array_values(array_unique(array_merge($context['tags'] ?? [], ['transfer_in']))),
                ])
            );

            $toSubAccount->balance = round((float) $toSubAccount->balance + $amount, 2);
            $toSubAccount->save();
            $this->autoUnlockIfEligible($toSubAccount);

            DB::afterCommit(function () use ($fromSubAccount, $toSubAccount, $amount) {
                $this->notificationService->notify(
                    $fromSubAccount->account->user,
                    'Wallet transfer complete',
                    sprintf('%.2f moved from %s to %s.', $amount, $fromSubAccount->name, $toSubAccount->name),
                    'info'
                );
            });

            return [
                'from' => $fromSubAccount->fresh(['paymentSplit']),
                'to' => $toSubAccount->fresh(['paymentSplit']),
            ];
        });
    }

    public function setLockState(SubAccount $subAccount, bool $locked): SubAccount
    {
        return DB::transaction(function () use ($subAccount, $locked) {
            $subAccount->refresh();
            $subAccount->locked = $locked;
            $subAccount->save();

            $this->logger->log(
                $subAccount->account,
                $locked ? 'sub_account_locked' : 'sub_account_unlocked',
                0,
                (float) $subAccount->balance,
                $locked
                    ? "Locked sub-account {$subAccount->name}."
                    : "Unlocked sub-account {$subAccount->name}.",
                $subAccount
            );

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function deleteSubAccount(SubAccount $subAccount): void
    {
        DB::transaction(function () use ($subAccount) {
            $subAccount->refresh();

            if ((float) $subAccount->balance > 0) {
                throw new InvalidArgumentException('Sub-account can only be deleted when its balance is zero.');
            }

            $account = $subAccount->account;
            $name = $subAccount->name;
            $subAccount->delete();

            $this->logger->log(
                $account,
                'sub_account_deleted',
                0,
                (float) $account->fresh()->balance,
                "Deleted sub-account {$name}."
            );
        });
    }

    public function moveFromMainToSubAccount(
        Account $account,
        SubAccount $subAccount,
        float $amount,
        array $context = [],
        string $description = 'Transfer from main account to savings wallet.'
    ): SubAccount {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($account, $subAccount, $amount, $context, $description) {
            $account->refresh();
            $subAccount->refresh();

            if ($subAccount->account_id !== $account->id) {
                throw new InvalidArgumentException('Destination wallet does not belong to this account.');
            }

            if ((float) $account->balance < $amount) {
                throw new InvalidArgumentException('Insufficient main account balance.');
            }

            $account->balance = round((float) $account->balance - $amount, 2);
            $account->save();

            $this->logger->log(
                $account,
                'main_to_sub_transfer',
                $amount,
                (float) $account->balance,
                $description,
                $subAccount,
                array_merge($context, [
                    'related_sub_account_id' => $subAccount->id,
                    'tags' => array_values(array_unique(array_merge($context['tags'] ?? [], ['main_transfer_out']))),
                ])
            );

            $subAccount->balance = round((float) $subAccount->balance + $amount, 2);
            $subAccount->save();

            $this->logger->log(
                $account,
                'sub_account_deposit',
                $amount,
                (float) $subAccount->balance,
                $description,
                $subAccount,
                array_merge($context, [
                    'related_sub_account_id' => null,
                    'tags' => array_values(array_unique(array_merge($context['tags'] ?? [], ['main_transfer_in']))),
                ])
            );

            $this->autoUnlockIfEligible($subAccount);

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function replacePaymentSplits(Account $account, array $splits): void
    {
        DB::transaction(function () use ($account, $splits) {
            $cleanSplits = collect($splits)
                ->filter(fn (array $split) => isset($split['sub_account_id'], $split['percentage']) && (float) $split['percentage'] > 0)
                ->map(fn (array $split) => [
                    'sub_account_id' => (int) $split['sub_account_id'],
                    'percentage' => round((float) $split['percentage'], 2),
                ]);

            $total = $cleanSplits->sum('percentage');

            if ($total > 100) {
                throw new InvalidArgumentException('Payment split total cannot exceed 100%.');
            }

            $subAccountIds = $account->subAccounts()->pluck('id');

            if ($cleanSplits->pluck('sub_account_id')->diff($subAccountIds)->isNotEmpty()) {
                throw new InvalidArgumentException('One or more split accounts do not belong to you.');
            }

            PaymentSplit::whereIn('sub_account_id', $subAccountIds)->delete();

            foreach ($cleanSplits as $split) {
                PaymentSplit::create($split);
            }

            $this->logger->log(
                $account,
                'payment_split_updated',
                0,
                (float) $account->fresh()->balance,
                sprintf('Updated payment split configuration to %.2f%% total.', $total)
            );
        });
    }

    public function clearPaymentSplits(Account $account): void
    {
        DB::transaction(function () use ($account) {
            PaymentSplit::whereIn('sub_account_id', $account->subAccounts()->pluck('id'))->delete();

            $this->logger->log(
                $account,
                'payment_split_cleared',
                0,
                (float) $account->fresh()->balance,
                'Cleared all payment split rules.'
            );
        });
    }

    protected function applyPaymentSplits(Account $account, float $depositAmount, Collection $subAccounts): void
    {
        $distributed = 0.0;
        $count = $subAccounts->count();

        foreach ($subAccounts->values() as $index => $subAccount) {
            $raw = $depositAmount * (((float) $subAccount->paymentSplit->percentage) / 100);
            $allocation = $index === $count - 1
                ? round($raw, 2)
                : floor(($raw * 100)) / 100;

            if ($allocation <= 0) {
                continue;
            }

            $distributed += $allocation;

            $account->balance = round((float) $account->balance - $allocation, 2);
            $account->save();
            $this->logger->log(
                $account,
                'auto_split_out',
                $allocation,
                (float) $account->balance,
                "Auto-split transfer to {$subAccount->name}.",
                $subAccount
            );

            $subAccount->balance = round((float) $subAccount->balance + $allocation, 2);
            $subAccount->save();
            $this->logger->log(
                $account,
                'auto_split_in',
                $allocation,
                (float) $subAccount->balance,
                "Auto-split allocation applied to {$subAccount->name}.",
                $subAccount
            );

            $this->autoUnlockIfEligible($subAccount);
        }

        $remainder = round($depositAmount - $distributed, 2);
        $this->logger->log(
            $account,
            'auto_split_summary',
            $remainder,
            (float) $account->balance,
            sprintf('Auto-split completed. %.2f stayed in main account.', $remainder)
        );
    }

    protected function autoUnlockIfEligible(SubAccount $subAccount): void
    {
        if (! $subAccount->shouldAutoUnlock()) {
            return;
        }

        $subAccount->locked = false;
        $subAccount->save();

        $this->logger->log(
            $subAccount->account,
            'sub_account_auto_unlocked',
            0,
            (float) $subAccount->balance,
            "Auto-unlocked {$subAccount->name} after reaching its target.",
            $subAccount
        );

        DB::afterCommit(function () use ($subAccount) {
            $this->notificationService->notify(
                $subAccount->account->user,
                'Goal unlocked',
                sprintf('%s has reached its target and is now unlocked.', $subAccount->name),
                'success'
            );
        });
    }

    protected function upsertPaymentSplit(Account $account, SubAccount $subAccount, float $percentage): void
    {
        $currentTotal = (float) $account->subAccounts()
            ->whereKeyNot($subAccount->id)
            ->with('paymentSplit')
            ->get()
            ->sum(fn (SubAccount $item) => (float) ($item->paymentSplit?->percentage ?? 0));

        if ($currentTotal + $percentage > 100) {
            throw new InvalidArgumentException('Payment split total cannot exceed 100%.');
        }

        $subAccount->paymentSplit()->updateOrCreate([], [
            'percentage' => round($percentage, 2),
        ]);
    }

    protected function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
    }
}

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
        protected TransactionLoggerService $logger
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

    public function depositToMain(Account $account, float $amount, string $description = 'Main account deposit.'): Account
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($account, $amount, $description) {
            $account->refresh();
            $account->balance = (float) $account->balance + $amount;
            $account->save();

            $this->logger->log($account, 'deposit', $amount, (float) $account->balance, $description);

            $splits = $account->subAccounts()
                ->with('paymentSplit')
                ->whereHas('paymentSplit')
                ->get();

            if ($splits->isNotEmpty()) {
                $this->applyPaymentSplits($account, $amount, $splits);
            }

            return $account->fresh(['subAccounts.paymentSplit']);
        });
    }

    public function withdrawFromMain(Account $account, float $amount, string $description = 'Main account withdrawal.'): Account
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($account, $amount, $description) {
            $account->refresh();

            if ((float) $account->balance < $amount) {
                throw new InvalidArgumentException('Insufficient main account balance.');
            }

            $account->balance = (float) $account->balance - $amount;
            $account->save();

            $this->logger->log($account, 'withdrawal', $amount, (float) $account->balance, $description);

            return $account->fresh();
        });
    }

    public function depositToSubAccount(SubAccount $subAccount, float $amount, string $description = 'Sub-account deposit.'): SubAccount
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($subAccount, $amount, $description) {
            $subAccount->refresh();
            $subAccount->balance = (float) $subAccount->balance + $amount;
            $subAccount->save();

            $this->logger->log(
                $subAccount->account,
                'sub_account_deposit',
                $amount,
                (float) $subAccount->balance,
                $description,
                $subAccount
            );

            $this->autoUnlockIfEligible($subAccount);

            return $subAccount->fresh(['paymentSplit']);
        });
    }

    public function withdrawFromSubAccount(SubAccount $subAccount, float $amount, string $description = 'Sub-account withdrawal.'): SubAccount
    {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($subAccount, $amount, $description) {
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
                $subAccount
            );

            return $subAccount->fresh(['paymentSplit']);
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

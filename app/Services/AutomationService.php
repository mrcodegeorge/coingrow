<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AutoSavingsRule;
use App\Models\ScheduledTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AutomationService
{
    public function __construct(
        protected BankingService $bankingService,
        protected UserNotificationService $notificationService
    ) {
    }

    public function createAutoSavingsRule(array $data): AutoSavingsRule
    {
        $rule = AutoSavingsRule::create([
            'user_id' => $data['user_id'],
            'sub_account_id' => $data['sub_account_id'],
            'type' => $data['type'],
            'value' => $data['value'],
            'frequency' => $data['frequency'],
            'active' => true,
            'next_run_at' => $data['frequency'] === AutoSavingsRule::FREQUENCY_PER_DEPOSIT ? null : $this->initialRunAt($data['frequency']),
        ]);

        $this->notificationService->notify(
            $rule->user,
            'Auto-savings enabled',
            sprintf('A %s auto-savings rule was created for %s.', $rule->frequency, $rule->subAccount->name),
            'success'
        );

        return $rule;
    }

    public function createScheduledTransaction(array $data): ScheduledTransaction
    {
        return ScheduledTransaction::create([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'source_sub_account_id' => $data['source_sub_account_id'] ?? null,
            'destination_sub_account_id' => $data['destination_sub_account_id'] ?? null,
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'next_run_at' => $this->initialRunAt($data['frequency']),
            'active' => true,
            'description' => $data['description'] ?? null,
        ]);
    }

    public function applyPerDepositRules(Account $account, float $depositAmount): void
    {
        $rules = $account->user->autoSavingsRules()
            ->where('active', true)
            ->where('frequency', AutoSavingsRule::FREQUENCY_PER_DEPOSIT)
            ->with('subAccount')
            ->get();

        foreach ($rules as $rule) {
            $amount = $this->amountForRule($rule, $depositAmount, (float) $account->balance);

            if ($amount <= 0 || (float) $account->fresh()->balance < $amount) {
                continue;
            }

            $this->bankingService->moveFromMainToSubAccount(
                $account,
                $rule->subAccount,
                $amount,
                [
                    'category' => 'savings',
                    'note' => 'Auto-savings applied on deposit.',
                    'tags' => ['auto_savings', 'per_deposit'],
                ],
                "Auto-savings transfer to {$rule->subAccount->name}."
            );
        }
    }

    public function processDueAutomations(): array
    {
        $processedRules = 0;
        $processedSchedules = 0;

        $dueRules = AutoSavingsRule::query()
            ->where('active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->with(['user.account', 'subAccount'])
            ->get();

        foreach ($dueRules as $rule) {
            $processedRules += $this->processAutoSavingsRule($rule) ? 1 : 0;
        }

        $dueScheduled = ScheduledTransaction::query()
            ->where('active', true)
            ->where('next_run_at', '<=', now())
            ->with(['user.account', 'sourceSubAccount', 'destinationSubAccount'])
            ->get();

        foreach ($dueScheduled as $scheduledTransaction) {
            $processedSchedules += $this->processScheduledTransaction($scheduledTransaction) ? 1 : 0;
        }

        return [
            'auto_savings_rules' => $processedRules,
            'scheduled_transactions' => $processedSchedules,
        ];
    }

    protected function processAutoSavingsRule(AutoSavingsRule $rule): bool
    {
        $account = $rule->user->account;
        $amount = $this->amountForRule($rule, null, (float) $account->balance);

        if ($amount <= 0 || (float) $account->balance < $amount) {
            $rule->next_run_at = $this->nextRunAt($rule->frequency, $rule->next_run_at ?? now());
            $rule->save();

            return false;
        }

        $this->bankingService->moveFromMainToSubAccount(
            $account,
            $rule->subAccount,
            $amount,
            [
                'category' => 'savings',
                'note' => 'Scheduled auto-savings executed.',
                'tags' => ['auto_savings', $rule->frequency],
            ],
            "Scheduled auto-savings transfer to {$rule->subAccount->name}."
        );

        $rule->next_run_at = $this->nextRunAt($rule->frequency, $rule->next_run_at ?? now());
        $rule->save();

        return true;
    }

    protected function processScheduledTransaction(ScheduledTransaction $scheduledTransaction): bool
    {
        if ($scheduledTransaction->type === ScheduledTransaction::TYPE_DEPOSIT) {
            $account = $scheduledTransaction->user->account;

            if (! $scheduledTransaction->destinationSubAccount || (float) $account->balance < (float) $scheduledTransaction->amount) {
                $scheduledTransaction->next_run_at = $this->nextRunAt($scheduledTransaction->frequency, $scheduledTransaction->next_run_at);
                $scheduledTransaction->save();

                return false;
            }

            $this->bankingService->moveFromMainToSubAccount(
                $account,
                $scheduledTransaction->destinationSubAccount,
                (float) $scheduledTransaction->amount,
                [
                    'category' => 'savings',
                    'note' => $scheduledTransaction->description,
                    'tags' => ['scheduled_transaction', 'deposit'],
                ],
                $scheduledTransaction->description ?: "Scheduled deposit to {$scheduledTransaction->destinationSubAccount->name}."
            );
        }

        if ($scheduledTransaction->type === ScheduledTransaction::TYPE_TRANSFER) {
            if (! $scheduledTransaction->sourceSubAccount || ! $scheduledTransaction->destinationSubAccount) {
                throw new InvalidArgumentException('Scheduled transfer must include both source and destination wallets.');
            }

            $this->bankingService->transferBetweenSubAccounts(
                $scheduledTransaction->sourceSubAccount,
                $scheduledTransaction->destinationSubAccount,
                (float) $scheduledTransaction->amount,
                [
                    'category' => 'transfer',
                    'note' => $scheduledTransaction->description,
                    'tags' => ['scheduled_transaction', 'transfer'],
                ]
            );
        }

        $scheduledTransaction->next_run_at = $this->nextRunAt($scheduledTransaction->frequency, $scheduledTransaction->next_run_at);
        $scheduledTransaction->save();

        return true;
    }

    protected function amountForRule(AutoSavingsRule $rule, ?float $depositAmount, float $mainBalance): float
    {
        if ($rule->type === AutoSavingsRule::TYPE_FIXED) {
            return round((float) $rule->value, 2);
        }

        $base = $depositAmount ?? $mainBalance;

        return round($base * (((float) $rule->value) / 100), 2);
    }

    protected function initialRunAt(string $frequency): ?Carbon
    {
        return match ($frequency) {
            AutoSavingsRule::FREQUENCY_DAILY, ScheduledTransaction::FREQUENCY_DAILY => now()->addDay(),
            AutoSavingsRule::FREQUENCY_WEEKLY, ScheduledTransaction::FREQUENCY_WEEKLY => now()->addWeek(),
            ScheduledTransaction::FREQUENCY_MONTHLY => now()->addMonth(),
            default => null,
        };
    }

    protected function nextRunAt(string $frequency, Carbon|string $from): Carbon
    {
        $base = $from instanceof Carbon ? $from->copy() : Carbon::parse($from);

        return match ($frequency) {
            AutoSavingsRule::FREQUENCY_DAILY, ScheduledTransaction::FREQUENCY_DAILY => $base->addDay(),
            AutoSavingsRule::FREQUENCY_WEEKLY, ScheduledTransaction::FREQUENCY_WEEKLY => $base->addWeek(),
            ScheduledTransaction::FREQUENCY_MONTHLY => $base->addMonth(),
            default => $base,
        };
    }
}

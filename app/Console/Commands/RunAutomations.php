<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class RunAutomations extends Command
{
    protected $signature = 'coingrow:run-automations';

    protected $description = 'Process due auto-savings rules and scheduled transactions.';

    public function handle(AutomationService $automationService): int
    {
        $result = $automationService->processDueAutomations();

        $this->info(sprintf(
            'Processed %d auto-savings rules and %d scheduled transactions.',
            $result['auto_savings_rules'],
            $result['scheduled_transactions']
        ));

        return self::SUCCESS;
    }
}

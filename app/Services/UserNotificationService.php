<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\BankingActivityNotification;

class UserNotificationService
{
    public function notify(User $user, string $title, string $message, string $level = 'info'): void
    {
        $user->notify(new BankingActivityNotification($title, $message, $level));
    }
}

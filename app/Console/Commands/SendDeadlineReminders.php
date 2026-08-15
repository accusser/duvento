<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Support\ReminderDispatcher;
use App\Support\ScheduleHeartbeat;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'duvento:send-reminders';

    protected $description = 'Отправить email-напоминания по правилам дедлайнов';

    public function handle(ReminderDispatcher $dispatcher): int
    {
        $total = Workspace::query()->whereNull('blocked_at')->get()
            ->sum(fn (Workspace $workspace) => $dispatcher->dispatchForWorkspace($workspace));

        ScheduleHeartbeat::touch('reminders');
        $this->info("Reminders sent: {$total}");

        return self::SUCCESS;
    }
}

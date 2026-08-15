<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class InstanceHealth
{
    /** @return list<array{key: string, label: string, ok: bool, level: string, detail: string}> */
    public function checks(): array
    {
        $scheduler = ScheduleHeartbeat::last('scheduler');
        $ssl = ScheduleHeartbeat::last('ssl');
        $reminders = ScheduleHeartbeat::last('reminders');
        $lastSslAsset = Asset::query()->whereNotNull('last_checked_at')->max('last_checked_at');
        $lastReminder = ActivityLog::query()->where('action', 'reminder.sent')->max('created_at');
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $queued = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');
        $smtpReady = $mailer === 'smtp' && filled($from) && filled(config('mail.mailers.smtp.host'));

        return [
            $this->check('scheduler', $scheduler, 10),
            $this->check('ssl', $ssl, 26 * 60, $lastSslAsset ? Carbon::parse($lastSslAsset)->toDateTimeString() : null),
            $this->check('reminders', $reminders, 26 * 60, $lastReminder ? Carbon::parse($lastReminder)->toDateTimeString() : null),
            [
                'key' => 'queue',
                'label' => __('admin.health.queue'),
                'ok' => $failed === 0,
                'level' => $failed === 0 ? 'ok' : 'stale',
                'detail' => __('admin.health.queue_detail', [
                    'queued' => $queued,
                    'failed' => $failed,
                    'driver' => config('queue.default'),
                ]),
            ],
            [
                'key' => 'smtp',
                'label' => __('admin.health.smtp'),
                'ok' => $smtpReady,
                'level' => $smtpReady ? 'ok' : 'warn',
                'detail' => $mailer === 'log'
                    ? __('admin.health.smtp_log_detail', ['from' => $from !== '' ? $from : __('admin.placeholders.empty')])
                    : __('admin.health.smtp_detail', [
                        'mailer' => $mailer,
                        'from' => $from !== '' ? $from : __('admin.placeholders.empty'),
                    ]),
            ],
        ];
    }

    /** @return list<array{id: int, failed_at: string, queue: string, exception: string}> */
    public function failedJobs(int $limit = 10): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($job) => [
                'id' => (int) $job->id,
                'failed_at' => (string) $job->failed_at,
                'queue' => (string) $job->queue,
                'exception' => Str::limit(preg_replace('/\s+/', ' ', (string) $job->exception) ?? '', 180),
            ])
            ->all();
    }

    /**
     * @return array{key: string, label: string, ok: bool, level: string, detail: string}
     */
    private function check(string $key, ?Carbon $at, int $staleMinutes, ?string $extra = null): array
    {
        $ok = $at !== null && $at->greaterThan(now()->subMinutes($staleMinutes));
        $when = $at?->toDateTimeString() ?? __('admin.placeholders.empty');
        $detail = __('admin.health.last_run', ['when' => $when]);

        if ($extra) {
            $detail .= ' · '.$extra;
        }

        return [
            'key' => $key,
            'label' => __('admin.health.'.$key),
            'ok' => $ok,
            'level' => $ok ? 'ok' : 'stale',
            'detail' => $detail,
        ];
    }
}

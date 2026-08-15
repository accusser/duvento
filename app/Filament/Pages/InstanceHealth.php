<?php

namespace App\Filament\Pages;

use App\Support\InstanceHealth as Health;
use App\Support\InstanceSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class InstanceHealth extends Page
{
    protected string $view = 'filament.pages.instance-health';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 20;

    public string $mailer = 'log';

    public string $host = '';

    public string $port = '587';

    public string $username = '';

    public string $password = '';

    public string $scheme = 'tls';

    public string $from_address = '';

    public string $from_name = '';

    public function mount(): void
    {
        $mail = app(InstanceSettings::class)->mail();
        $this->mailer = $mail['mailer'];
        $this->host = $mail['host'];
        $this->port = $mail['port'] !== '' ? $mail['port'] : '587';
        $this->username = $mail['username'];
        $this->scheme = $mail['scheme'] !== '' ? $mail['scheme'] : 'tls';
        $this->from_address = $mail['from_address'];
        $this->from_name = $mail['from_name'];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.health.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.health.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('admin.health.description');
    }

    protected function getViewData(): array
    {
        $health = app(Health::class);

        return [
            'checks' => $health->checks(),
            'failedJobs' => $health->failedJobs(),
            'cron' => app(InstanceSettings::class)->cronCommand(),
        ];
    }

    public function saveMail(InstanceSettings $settings): void
    {
        $this->validate([
            'mailer' => ['required', Rule::in(['log', 'smtp'])],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:160'],
            'host' => [$this->mailer === 'smtp' ? 'required' : 'nullable', 'string', 'max:255'],
            'port' => [$this->mailer === 'smtp' ? 'required' : 'nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'scheme' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
        ], [
            'required' => __('admin.validation.required'),
            'email' => __('admin.validation.email'),
            'max' => __('admin.validation.max'),
        ]);

        try {
            $settings->saveMail([
                'mailer' => $this->mailer,
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'scheme' => $this->scheme,
                'from_address' => $this->from_address,
                'from_name' => $this->from_name,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title(__('admin.health.mail_migrate'))->danger()->send();

            return;
        }
        $this->password = '';

        Notification::make()->title(__('admin.health.mail_saved'))->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('smtp')
                ->label(__('admin.health.smtp_send'))
                ->action(function (): void {
                    $email = auth('admin')->user()?->email;

                    try {
                        Mail::raw(__('app.account.smtp_body'), function ($message) use ($email) {
                            $message->to($email)->subject(__('app.account.smtp_subject'));
                        });
                        Notification::make()->title(__('app.flash.smtp_sent', ['email' => $email]))->success()->send();
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()->title(__('app.flash.smtp_failed'))->danger()->send();
                    }
                }),
            Action::make('runSsl')
                ->label(__('admin.health.run_ssl'))
                ->action(function (): void {
                    Artisan::call('duvento:check-ssl');
                    Notification::make()->title(__('admin.health.run_done'))->success()->send();
                }),
            Action::make('runReminders')
                ->label(__('admin.health.run_reminders'))
                ->action(function (): void {
                    Artisan::call('duvento:send-reminders');
                    Notification::make()->title(__('admin.health.run_done'))->success()->send();
                }),
        ];
    }
}

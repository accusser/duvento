<?php

namespace App\Providers;

use App\Contracts\BillingGateway;
use App\Install\InstallBootstrap;
use App\Install\InstallerState;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Models\AdminUser;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Client;
use App\Models\PaymentEvent;
use App\Models\ReminderRule;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\WaitlistSignup;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Observers\AdminActivityObserver;
use App\Support\InstanceSettings;
use App\Support\LocalBillingGateway;
use App\Support\SqliteUnicode;
use Duvento\Cloud\CloudServiceProvider;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            InstallBootstrap::beforeHttp();
        }

        Event::listen(
            ConnectionEstablished::class,
            fn (ConnectionEstablished $event) => SqliteUnicode::register($event->connection),
        );

        if (! class_exists(CloudServiceProvider::class)) {
            $this->app->bind(BillingGateway::class, LocalBillingGateway::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Livewire::component('workspace-notifications', NotificationsIndex::class);

        Relation::morphMap([
            'client' => User::class,
            'admin' => AdminUser::class,
        ]);

        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::End);

        if (InstallerState::isLocked()) {
            app(InstanceSettings::class)->apply();
        }

        foreach ([
            AdminUser::class,
            Asset::class,
            AssetType::class,
            Client::class,
            PaymentEvent::class,
            ReminderRule::class,
            Subscription::class,
            Ticket::class,
            TicketMessage::class,
            User::class,
            WaitlistSignup::class,
            Workspace::class,
            WorkspaceInvitation::class,
        ] as $model) {
            $model::observe(AdminActivityObserver::class);
        }
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CsvDownload;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportData extends Page
{
    protected string $view = 'filament.pages.export-data';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 21;

    public string $dataset = 'workspaces';

    public static function getNavigationLabel(): string
    {
        return __('admin.export.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.export.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('admin.export.description');
    }

    /**
     * @return array<string, string>
     */
    public function datasets(): array
    {
        return [
            'workspaces' => __('admin.resources.workspaces.plural'),
            'users' => __('admin.resources.users.plural'),
            'clients' => __('admin.resources.clients.plural'),
            'assets' => __('admin.resources.assets.plural'),
            'activity' => __('admin.resources.activity.plural'),
            'tickets' => __('admin.resources.tickets.plural'),
            'admins' => __('admin.resources.admins.plural'),
        ];
    }

    public function export(): StreamedResponse
    {
        abort_unless(array_key_exists($this->dataset, $this->datasets()), 422);

        return match ($this->dataset) {
            'workspaces' => $this->exportWorkspaces(),
            'users' => $this->exportUsers(),
            'clients' => $this->exportClients(),
            'assets' => $this->exportAssets(),
            'activity' => $this->exportActivity(),
            'tickets' => $this->exportTickets(),
            'admins' => $this->exportAdmins(),
        };
    }

    private function exportWorkspaces(): StreamedResponse
    {
        $records = Workspace::query()
            ->withCount(['users', 'clients', 'assets'])
            ->orderBy('name')
            ->get();

        return $this->download('workspaces', [
            __('admin.fields.name'),
            __('admin.fields.plan'),
            __('admin.fields.users'),
            __('admin.fields.clients'),
            __('admin.fields.assets'),
            __('admin.fields.blocked_at'),
            __('admin.fields.created_at'),
        ], $records->map(fn (Workspace $workspace): array => [
            $workspace->name,
            $workspace->plan->label(),
            $workspace->users_count,
            $workspace->clients_count,
            $workspace->assets_count,
            $workspace->blocked_at?->toDateTimeString(),
            $workspace->created_at?->toDateTimeString(),
        ]));
    }

    private function exportUsers(): StreamedResponse
    {
        $records = User::query()->with('workspaces')->orderBy('name')->get();

        return $this->download('users', [
            __('admin.fields.name'),
            __('admin.fields.email'),
            __('admin.fields.workspaces'),
            __('admin.fields.created_at'),
        ], $records->map(fn (User $user): array => [
            $user->name,
            $user->email,
            $user->workspaces->map(fn (Workspace $workspace): string => $workspace->name.' ('.$workspace->pivot->role.')')->join(', '),
            $user->created_at?->toDateTimeString(),
        ]));
    }

    private function exportClients(): StreamedResponse
    {
        $records = Client::query()->with('workspace')->withCount('assets')->orderBy('name')->get();

        return $this->download('clients', [
            __('admin.fields.name'),
            __('admin.fields.workspace'),
            __('admin.fields.contact_name'),
            __('admin.fields.email'),
            __('admin.fields.website'),
            __('admin.fields.assets'),
            __('admin.fields.created_at'),
        ], $records->map(fn (Client $client): array => [
            $client->name,
            $client->workspace?->name,
            $client->contact_name,
            $client->email,
            $client->website,
            $client->assets_count,
            $client->created_at?->toDateTimeString(),
        ]));
    }

    private function exportAssets(): StreamedResponse
    {
        $records = Asset::query()
            ->with(['workspace', 'client', 'assetType'])
            ->orderBy('expires_at')
            ->get();

        return $this->download('assets', [
            __('admin.fields.name'),
            __('admin.fields.workspace'),
            __('admin.fields.client'),
            __('admin.fields.type'),
            __('admin.fields.expires_at'),
            __('admin.fields.status'),
            __('admin.fields.owner'),
            __('admin.fields.payer'),
            __('admin.fields.created_at'),
        ], $records->map(fn (Asset $asset): array => [
            $asset->name,
            $asset->workspace?->name,
            $asset->client?->name,
            $asset->assetType?->displayLabel(),
            $asset->expires_at?->toDateString(),
            $asset->status->label(),
            $asset->owner->value,
            $asset->payer->value,
            $asset->created_at?->toDateTimeString(),
        ]));
    }

    private function exportActivity(): StreamedResponse
    {
        $records = ActivityLog::query()->with(['workspace', 'user'])->latest()->get();

        return $this->download('activity', [
            __('admin.fields.when'),
            __('admin.fields.workspace'),
            __('admin.fields.who'),
            __('admin.fields.action'),
            __('admin.fields.properties'),
        ], $records->map(fn (ActivityLog $log): array => [
            $log->created_at?->toDateTimeString(),
            $log->workspace?->name,
            $log->user?->name,
            $log->action,
            json_encode($log->properties, JSON_UNESCAPED_UNICODE),
        ]));
    }

    private function exportAdmins(): StreamedResponse
    {
        $records = AdminUser::query()->orderBy('name')->get();

        return $this->download('admins', [
            __('admin.fields.name'),
            __('admin.fields.email'),
            __('admin.fields.phone'),
            __('admin.fields.telegram'),
            __('admin.fields.status'),
            __('admin.fields.created_at'),
        ], $records->map(fn (AdminUser $admin): array => [
            $admin->name,
            $admin->email,
            $admin->phone,
            $admin->telegram,
            $admin->blocked_at ? __('admin.filters.blocked') : __('admin.filters.active'),
            $admin->created_at?->toDateTimeString(),
        ]));
    }

    private function exportTickets(): StreamedResponse
    {
        $records = Ticket::query()->with(['workspace', 'user'])->latest('last_message_at')->get();

        return $this->download('tickets', [
            __('admin.fields.workspace'),
            __('admin.tickets.subject'),
            __('admin.fields.who'),
            __('admin.fields.status'),
            __('admin.tickets.priority'),
            __('admin.tickets.last_message'),
        ], $records->map(fn (Ticket $ticket): array => [
            $ticket->workspace?->name,
            $ticket->subject,
            $ticket->user?->name,
            $ticket->status->label(),
            $ticket->priority->label(),
            $ticket->last_message_at?->toDateTimeString(),
        ]));
    }

    private function download(string $dataset, array $headers, iterable $rows): StreamedResponse
    {
        return CsvDownload::stream(
            'duvento-'.$dataset.'-'.now()->format('Y-m-d').'.csv',
            $headers,
            $rows,
        );
    }
}

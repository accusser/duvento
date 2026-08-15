<?php

namespace App\Http\Controllers;

use App\Support\AssetQuery;
use App\Support\CsvDownload;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportController extends Controller
{
    public function assets(Request $request): StreamedResponse
    {
        $this->assertWorkspaceOwner($request);
        $workspace = $request->user()->currentWorkspace;
        $assets = AssetQuery::filtered(
            $workspace,
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->integer('clientId') ?: null,
            [
                'typeId' => $request->integer('typeId') ?: null,
                'owner' => $request->string('owner')->toString() ?: null,
                'payer' => $request->string('payer')->toString() ?: null,
                'expiry' => $request->string('expiry')->toString() ?: null,
            ],
        );

        return CsvDownload::stream('duvento-assets-'.now()->format('Y-m-d').'.csv', [
            'Name', 'Type', 'Client', 'Expires at', 'Days left', 'Status', 'Owner', 'Payer', 'Auto renew', 'Notice email', 'Renewal cost', 'Currency',
        ], $assets->map(fn ($asset) => [
            $asset->name,
            $asset->assetType?->displayLabel(),
            $asset->client?->name,
            $asset->expires_at?->toDateString(),
            $asset->days_left,
            $asset->status->label(),
            $asset->owner->value,
            $asset->payer->value,
            $asset->auto_renew->value,
            $asset->notice_email,
            $asset->renewal_cost,
            $asset->currency,
        ]));
    }

    public function clients(Request $request): StreamedResponse
    {
        $this->assertWorkspaceOwner($request);
        $clients = $request->user()->currentWorkspace->clients()->withCount('assets')->orderBy('name')->get();

        return CsvDownload::stream('duvento-clients-'.now()->format('Y-m-d').'.csv', [
            'Name', 'Contact', 'Email', 'Website', 'Notes', 'Assets',
        ], $clients->map(fn ($client) => [
            $client->name,
            $client->contact_name,
            $client->email,
            $client->website,
            $client->notes,
            $client->assets_count,
        ]));
    }

    public function activity(Request $request): StreamedResponse
    {
        $this->assertWorkspaceOwner($request);
        $logs = $request->user()->currentWorkspace->activityLogs()->with('user')->latest()->limit(2000)->get();

        return CsvDownload::stream('duvento-activity-'.now()->format('Y-m-d').'.csv', [
            'When', 'Action', 'User', 'Name',
        ], $logs->map(fn ($log) => [
            $log->created_at->toDateTimeString(),
            $log->action,
            $log->user?->name,
            $log->properties['name'] ?? '',
        ]));
    }

    public function clientsTemplate(): StreamedResponse
    {
        return CsvDownload::stream('duvento-clients-template.csv', ['name', 'contact', 'email', 'website', 'notes'], []);
    }

    public function assetsTemplate(): StreamedResponse
    {
        return CsvDownload::stream('duvento-assets-template.csv', [
            'name', 'type', 'client', 'expires_at', 'owner', 'payer', 'auto_renew', 'notice_email', 'ssl_check', 'notes', 'renewal_cost', 'currency',
        ], []);
    }

    private function assertWorkspaceOwner(Request $request): void
    {
        abort_unless($request->user()->ownsCurrentWorkspace(), 403);
    }
}

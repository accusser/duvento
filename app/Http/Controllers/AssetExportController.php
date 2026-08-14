<?php

namespace App\Http\Controllers;

use App\Support\AssetQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $assets = AssetQuery::filtered(
            $workspace,
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
            $request->integer('clientId') ?: null,
        );

        $filename = 'duvento-assets-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($assets) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Type', 'Client', 'Expires at', 'Days left', 'Status', 'Owner', 'Payer', 'Auto renew', 'Notice email']);

            foreach ($assets as $asset) {
                fputcsv($handle, [
                    $asset->name,
                    $asset->assetType?->label,
                    $asset->client?->name,
                    $asset->expires_at?->toDateString(),
                    $asset->days_left,
                    $asset->status->label(),
                    $asset->owner->value,
                    $asset->payer->value,
                    $asset->auto_renew->value,
                    $asset->notice_email,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

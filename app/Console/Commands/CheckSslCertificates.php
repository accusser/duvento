<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Support\ActivityLogger;
use App\Support\SslCertificateInspector;
use Illuminate\Console\Command;

class CheckSslCertificates extends Command
{
    protected $signature = 'duvento:check-ssl';

    protected $description = 'Обновить expires_at у SSL-активов по сертификату с порта 443';

    public function handle(SslCertificateInspector $inspector): int
    {
        $assets = Asset::query()
            ->with(['assetType', 'workspace'])
            ->where('ssl_check_enabled', true)
            ->whereHas('assetType', fn ($q) => $q->where('key', 'ssl'))
            ->get();

        foreach ($assets as $asset) {
            $host = $asset->hostname();

            if (! $host) {
                continue;
            }

            $expiry = $inspector->expiryFor($host);
            $asset->forceFill(['last_checked_at' => now()])->save();

            if ($expiry === null) {
                ActivityLogger::log($asset->workspace, 'ssl.check_failed', $asset, ['host' => $host], null);
                continue;
            }

            $previous = $asset->expires_at?->toDateString();
            $next = $expiry->toDateString();

            if ($previous !== $next) {
                $asset->forceFill(['expires_at' => $next])->save();
                ActivityLogger::log($asset->workspace, 'ssl.updated', $asset, [
                    'host' => $host,
                    'from' => $previous,
                    'to' => $next,
                ], null);
            }
        }

        $this->info('SSL check finished: '.$assets->count().' assets');

        return self::SUCCESS;
    }
}

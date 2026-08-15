<?php

namespace Tests\Unit;

use App\Enums\AssetPayer;
use App\Models\Asset;
use App\Support\UpcomingPayments;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UpcomingPaymentsTest extends TestCase
{
    public function test_summarize_skips_unpriced_and_out_of_window(): void
    {
        $assets = new Collection([
            $this->asset(['renewal_cost' => '100.00', 'currency' => 'USD', 'expires_at' => now()->addDays(5), 'payer' => AssetPayer::Agency]),
            $this->asset(['renewal_cost' => null, 'currency' => 'USD', 'expires_at' => now()->addDays(5), 'payer' => AssetPayer::Agency]),
            $this->asset(['renewal_cost' => '50.00', 'currency' => 'EUR', 'expires_at' => now()->addDays(40), 'payer' => AssetPayer::Client]),
        ]);

        $summary = UpcomingPayments::summarize($assets, 30, 'USD');

        $this->assertSame(1, $summary['count']);
        $this->assertSame(['USD' => '100.00'], $summary['by_currency']);
        $this->assertSame('$100', $summary['total_label']);
        $this->assertSame('$100', $summary['payer_labels']['agency']);
        $this->assertSame('', $summary['payer_labels']['client']);
        $this->assertCount(6, $summary['trend']['months']);
    }

    public function test_trend_groups_priced_assets_by_month(): void
    {
        $near = now()->addDay();
        $later = now()->addMonths(2);
        $assets = new Collection([
            $this->asset(['renewal_cost' => '100.00', 'currency' => 'USD', 'expires_at' => $near, 'payer' => AssetPayer::Agency]),
            $this->asset(['renewal_cost' => '40.00', 'currency' => 'USD', 'expires_at' => $later, 'payer' => AssetPayer::Client]),
            $this->asset(['renewal_cost' => null, 'currency' => 'USD', 'expires_at' => $near, 'payer' => AssetPayer::Agency]),
        ]);

        $trend = UpcomingPayments::trend($assets, 'USD');
        $byKey = collect($trend['months'])->keyBy('key');

        $this->assertSame('100.00', $byKey[$near->format('Y-m')]['by_currency']['USD']);
        $this->assertSame('40.00', $byKey[$later->format('Y-m')]['by_currency']['USD']);
        $this->assertSame(100.0, $trend['max']);
    }

    public function test_invalid_period_falls_back_to_thirty(): void
    {
        $this->assertSame(30, UpcomingPayments::normalizePeriod(5));
        $this->assertSame(7, UpcomingPayments::normalizePeriod('7'));
    }

    private function asset(array $attributes): Asset
    {
        $asset = new Asset;
        $asset->forceFill($attributes);
        $asset->payer = $attributes['payer'];
        $asset->expires_at = $attributes['expires_at'];

        return $asset;
    }
}

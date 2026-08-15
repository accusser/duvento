<?php

namespace App\Livewire\Settings;

use App\Contracts\BillingGateway;
use App\Enums\WorkspacePlan;
use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\Edition;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Billing extends Component
{
    use InteractsWithWorkspace;

    public function checkout(string $plan, BillingGateway $gateway)
    {
        abort_unless(Edition::enabled('billing'), 404);
        $this->assertOwner();

        return redirect()->away(
            $gateway->checkoutUrl($this->workspace(), WorkspacePlan::from($plan)),
        );
    }

    public function render()
    {
        abort_unless(Edition::enabled('billing'), 404);
        $this->assertOwner();

        $workspace = $this->workspace();
        $plan = $workspace->plan->value;
        $limits = config('billing.plans.'.$plan, []);
        $subscription = $workspace->subscriptions()->latest()->first();
        $events = $workspace->paymentEvents()->latest()->limit(20)->get();

        return view('livewire.settings.billing', compact('workspace', 'plan', 'limits', 'subscription', 'events'))
            ->title(__('app.titles.billing'));
    }
}

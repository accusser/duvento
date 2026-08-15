<?php

namespace App\Http\Controllers;

use App\Contracts\BillingGateway;
use App\Enums\WorkspacePlan;
use App\Support\BillingService;
use App\Support\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request, BillingGateway $gateway): RedirectResponse
    {
        abort_unless(Edition::enabled('billing'), 404);
        abort_unless($request->user()->ownsCurrentWorkspace(), 403);

        $plan = WorkspacePlan::from($request->validate(['plan' => ['required', 'in:starter,agency']])['plan']);
        $workspace = $request->user()->currentWorkspace;

        return redirect()->away($gateway->checkoutUrl($workspace, $plan));
    }

    public function simulate(Request $request, string $plan, BillingService $billing): RedirectResponse
    {
        abort_unless(Edition::allowsSandboxBilling(), 404);
        abort_unless($request->user()->ownsCurrentWorkspace(), 403);
        abort_unless($request->user()->current_workspace_id === (int) $request->query('workspace', $request->user()->current_workspace_id), 403);

        $workspace = $request->user()->currentWorkspace;
        $target = WorkspacePlan::from($plan);

        $billing->activate(
            $workspace,
            $target,
            'fake_'.uniqid(),
            (int) config('billing.plans.'.$target->value.'.price', 0) * 100,
        );

        return redirect()->route('settings.billing')->with('status', __('app.billing.activated'));
    }

    public function cancel(Request $request, BillingGateway $gateway): RedirectResponse
    {
        abort_unless(Edition::enabled('billing'), 404);
        abort_unless($request->user()->ownsCurrentWorkspace(), 403);
        $gateway->cancel($request->user()->currentWorkspace);

        return redirect()->route('settings.billing')->with('status', __('app.billing.cancelled'));
    }
}

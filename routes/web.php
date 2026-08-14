<?php

use App\Http\Controllers\AssetExportController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\WaitlistController;
use App\Livewire\Activity\Index as ActivityIndex;
use App\Livewire\Assets\Index as AssetsIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Settings\AssetTypes;
use App\Livewire\Settings\Billing;
use App\Livewire\Settings\Reminders;
use App\Models\Asset;
use App\Support\Edition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $assets = Asset::query()
        ->with(['client', 'assetType', 'workspace'])
        ->whereHas('workspace', fn ($q) => $q->where('name', 'Северная студия'))
        ->orderByRaw('expires_at is null')
        ->orderBy('expires_at')
        ->limit(4)
        ->get();

    return view('welcome', [
        'assets' => $assets,
        'edition' => Edition::current(),
    ]);
})->name('home');

Route::post('/waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');
    Route::get('/clients', ClientsIndex::class)->name('clients');
    Route::get('/assets', AssetsIndex::class)->name('assets');
    Route::get('/assets/export', AssetExportController::class)->name('assets.export');
    Route::get('/activity', ActivityIndex::class)->name('activity');
    Route::get('/settings/reminders', Reminders::class)->name('settings.reminders');
    Route::get('/settings/types', AssetTypes::class)->name('settings.types');
    Route::get('/settings/billing', Billing::class)->name('settings.billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/simulate/{plan}', [BillingController::class, 'simulate'])->name('billing.simulate');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/reports/clients', function () {
        abort_unless(class_exists(\Duvento\Cloud\Reports\WhiteLabelReport::class), 404);

        return app(\Duvento\Cloud\Reports\WhiteLabelReport::class)->view(auth()->user()->currentWorkspace);
    })->name('reports.clients');
});

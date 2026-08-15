<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CsvExportController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\VerifyEmailController;
use App\Http\Controllers\WaitlistController;
use App\Livewire\Activity\Index as ActivityIndex;
use App\Livewire\Assets\Form as AssetForm;
use App\Livewire\Assets\Index as AssetsIndex;
use App\Livewire\Assets\Show as AssetShow;
use App\Livewire\Auth\AcceptInvite;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Clients\Show;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Export\Index as ExportIndex;
use App\Livewire\Import\Index as ImportIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Public\ClientStatus;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Reports\Show as ReportShow;
use App\Livewire\Settings\Account;
use App\Livewire\Settings\Api;
use App\Livewire\Settings\AssetTypes;
use App\Livewire\Settings\Billing;
use App\Livewire\Settings\Reminders;
use App\Livewire\Settings\Team;
use App\Livewire\Support\Index as SupportIndex;
use App\Livewire\Support\Show as SupportShow;
use App\Support\AppLocale;
use App\Support\Edition;
use App\Support\LandingPreview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__.'/install.php';

Route::get('/', function () {
    app()->setLocale('en');

    return view('welcome', [
        'assets' => LandingPreview::assets(),
        'edition' => Edition::current(),
    ]);
})->name('home');

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(AppLocale::isSupported($locale), 404);
    session(['locale' => $locale]);

    return redirect()
        ->back(fallback: url('/'))
        ->cookie('duvento-locale', $locale, 60 * 24 * 365);
})->name('locale');

Route::post('/waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::get('/invites/{token}', AcceptInvite::class)
    ->where('token', '[a-z0-9]{32,64}')
    ->name('invites.show');

Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('impersonation.stop');

Route::get('/s/{token}', ClientStatus::class)
    ->where('token', '[a-z0-9]{32,64}')
    ->name('share.show');

Route::get('/ticket-attachments/{attachment}', TicketAttachmentController::class)
    ->middleware(['auth:web,admin', 'throttle:120,1'])
    ->whereNumber('attachment')
    ->name('ticket-attachments.download');

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::get('/dashboard', DashboardIndex::class)->name('dashboard');
    Route::get('/clients', ClientsIndex::class)->name('clients');
    Route::get('/clients/{client}', Show::class)->name('clients.show');
    Route::get('/assets', AssetsIndex::class)->name('assets');
    Route::get('/assets/create', AssetForm::class)->name('assets.create');
    Route::get('/assets/{asset}/edit', AssetForm::class)->name('assets.edit');
    Route::get('/assets/{asset}', AssetShow::class)->name('assets.show');
    Route::get('/activity', ActivityIndex::class)->name('activity');
    Route::get('/reports', ReportsIndex::class)->name('reports');
    Route::get('/reports/{client}', ReportShow::class)->whereNumber('client')->name('reports.show');
    Route::get('/export', ExportIndex::class)->name('export');
    Route::get('/import', ImportIndex::class)->name('import');
    Route::get('/notifications', NotificationsIndex::class)->name('notifications');
    Route::get('/support', SupportIndex::class)->name('support');
    Route::get('/support/{ticket}', SupportShow::class)->whereNumber('ticket')->name('support.show');
    Route::get('/export/assets', [CsvExportController::class, 'assets'])->name('export.assets');
    Route::get('/export/clients', [CsvExportController::class, 'clients'])->name('export.clients');
    Route::get('/export/activity', [CsvExportController::class, 'activity'])->name('export.activity');
    Route::get('/export/templates/clients', [CsvExportController::class, 'clientsTemplate'])->name('export.clients.template');
    Route::get('/export/templates/assets', [CsvExportController::class, 'assetsTemplate'])->name('export.assets.template');
    Route::redirect('/settings', '/settings/account')->name('settings');
    Route::get('/settings/account', Account::class)->name('settings.account');
    Route::get('/settings/reminders', Reminders::class)->name('settings.reminders');
    Route::get('/settings/team', Team::class)->name('settings.team');
    Route::get('/settings/types', AssetTypes::class)->name('settings.types');
    Route::get('/settings/billing', Billing::class)->name('settings.billing');
    Route::get('/settings/api', Api::class)->name('settings.api');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/simulate/{plan}', [BillingController::class, 'simulate'])->name('billing.simulate');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
});

<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditAdminProfile;
use App\Filament\Widgets\LatestUsers;
use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\SetAdminLocale;
use App\Support\AdminPath;
use App\Support\AppLocale;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(AdminPath::prefix())
            ->login()
            ->authGuard('admin')
            ->brandName('Duvento')
            ->favicon(asset('theme/images/logo.svg'))
            ->brandLogo(fn (): HtmlString => new HtmlString(
                '<span class="brand"><span class="brand-mark">D</span><span class="brand-text">Duvento</span></span>'
            ))
            ->brandLogoHeight('38px')
            ->font('Inter', asset('theme/css/fonts.css'), LocalFontProvider::class)
            ->colors([
                'primary' => Color::hex('#007257'),
            ])
            ->darkMode()
            ->defaultThemeMode(ThemeMode::Light)
            ->themeSwitcher(false)
            ->topbar(false)
            ->maxContentWidth('full')
            ->spa()
            // Locale switching re-renders every server-side translation, so it must be a full visit.
            ->spaUrlExceptions([
                '*/'.AdminPath::prefix().'/locale/*',
            ])
            ->profile(EditAdminProfile::class, isSimple: false)
            ->authenticatedRoutes(function (): void {
                Route::get('/locale/{locale}', function (string $locale) {
                    abort_unless(AppLocale::isSupported($locale), 404);
                    session(['admin_locale' => $locale]);

                    return redirect()->back(fallback: url(AdminPath::url()));
                })->name('locale');
            })
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): HtmlString {
                    $v = fn (string $path): int => filemtime(public_path($path));

                    return new HtmlString(
                        '<link rel="stylesheet" href="'.e(asset('theme/css/fonts.css')).'?v='.$v('theme/css/fonts.css').'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/vendor/bootstrap/css/bootstrap.min.css')).'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/vendor/mdi/css/materialdesignicons.min.css')).'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/vendor/remixicon/remixicon.css')).'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/css/app.css')).'?v='.$v('theme/css/app.css').'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/css/duvento.css')).'?v='.$v('theme/css/duvento.css').'" data-navigate-track>'
                        .'<link rel="stylesheet" href="'.e(asset('theme/fila/admin.css')).'?v='.$v('theme/fila/admin.css').'" data-navigate-track>'
                    );
                },
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(
                    '<script src="'.e(asset('theme/vendor/jquery/jquery.min.js')).'" data-navigate-once></script>'
                    .'<script src="'.e(asset('theme/vendor/bootstrap/js/bootstrap.bundle.min.js')).'" data-navigate-once></script>'
                    .'<script src="'.e(asset('theme/js/app.js')).'?v='.filemtime(public_path('theme/js/app.js')).'" data-navigate-once></script>'
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                LatestUsers::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                SetAdminLocale::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

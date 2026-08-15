<?php

namespace App\Http\Controllers;

use App\Install\EnvironmentChecker;
use App\Install\InstallerState;
use App\Install\InstallService;
use App\Support\AdminPath;
use App\Support\AppLocale;
use App\Support\InstanceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class InstallController extends Controller
{
    public function index(Request $request, EnvironmentChecker $checker): View
    {
        $step = InstallerState::step($request);
        $locale = (string) $request->session()->get('install.locale', 'ru');
        app()->setLocale($locale);

        return view('install.wizard', [
            'step' => $step,
            'locales' => AppLocale::all(),
            'checks' => $step === 'environment' ? $checker->check() : null,
            'adminPath' => (string) $request->session()->get('install.admin_path', AdminPath::generate()),
        ]);
    }

    public function locale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(AppLocale::codes())],
        ]);

        $request->session()->put('install.locale', $data['locale']);
        InstallerState::setStep($request, 'environment');

        return redirect()->route('install.index');
    }

    public function environment(Request $request, EnvironmentChecker $checker): RedirectResponse
    {
        abort_unless(InstallerState::step($request) === 'environment', 409);

        if (! $checker->check()['ok']) {
            return back()->withErrors(['environment' => 'Исправьте ошибки окружения и повторите проверку.']);
        }

        InstallerState::setStep($request, 'database');

        return redirect()->route('install.index');
    }

    public function database(Request $request, InstallService $installer): RedirectResponse
    {
        abort_unless(InstallerState::step($request) === 'database', 409);

        $data = $request->validate([
            'connection' => ['required', Rule::in(['mysql', 'sqlite'])],
            'host' => ['required_if:connection,mysql', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:connection,mysql', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_if:connection,mysql', 'nullable', 'string', 'max:128'],
            'username' => ['required_if:connection,mysql', 'nullable', 'string', 'max:128'],
            'password' => ['nullable', 'string', 'max:512'],
        ]);

        try {
            $installer->configureDatabase($data, $request->getSchemeAndHttpHost());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput($request->except('password'))
                ->withErrors(['database' => 'Подключение не удалось: '.$exception->getMessage()]);
        }

        InstallerState::setStep($request, 'migrate');

        return redirect()->route('install.index');
    }

    public function migrate(Request $request, InstallService $installer): RedirectResponse
    {
        abort_unless(InstallerState::step($request) === 'migrate', 409);

        try {
            $installer->migrate();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['migrate' => $exception->getMessage()]);
        }

        $request->session()->put('install.admin_path', AdminPath::generate());
        InstallerState::setStep($request, 'admin');

        return redirect()->route('install.index');
    }

    public function admin(Request $request, InstallService $installer, InstanceSettings $settings): View|RedirectResponse
    {
        abort_unless(InstallerState::step($request) === 'admin', 409);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'workspace' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'admin_path' => [
                'required',
                'string',
                'max:32',
                'regex:/^[a-z0-9][a-z0-9_-]{1,31}$/',
                Rule::notIn(AdminPath::reserved()),
            ],
        ]);
        $data['locale'] = (string) $request->session()->get('install.locale', 'ru');
        $appUrl = $request->getSchemeAndHttpHost();

        try {
            $installer->finish($data, $appUrl, $request->isSecure());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['admin' => 'Установка не завершена: '.$exception->getMessage()]);
        }

        return view('install.done', [
            'appUrl' => $appUrl,
            'adminUrl' => $appUrl.AdminPath::url(),
            'email' => $data['email'],
            'cron' => $settings->cronCommand(),
        ]);
    }
}

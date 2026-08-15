<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithWorkspace;
use App\Support\AccountDeleter;
use App\Support\PasswordGenerator;
use App\Support\RateLimits;
use App\Support\UpcomingPayments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Account extends Component
{
    use InteractsWithWorkspace;

    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $deletePassword = '';

    public string $workspaceCurrency = 'USD';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->workspaceCurrency = $this->workspace()->currencyCode();
    }

    public function generatePassword(): void
    {
        $password = PasswordGenerator::generate();

        $this->password = $password;
        $this->passwordConfirmation = $password;
        $this->resetValidation(['password', 'passwordConfirmation']);
    }

    public function save(): void
    {
        $user = auth()->user();
        $emailChanged = $user->email !== $this->email;
        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if ($emailChanged || $this->password !== '') {
            $rules['currentPassword'] = ['required', 'current_password'];
        }

        if ($this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:8', 'same:passwordConfirmation'];
        }

        $validated = $this->validate($rules);
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if (($validated['password'] ?? '') !== '') {
            $user->password = $validated['password'];
        }

        $user->save();
        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        $this->toast(__('app.flash.account_saved'));

        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function saveWorkspace(): void
    {
        $this->assertOwner();
        $this->workspaceCurrency = UpcomingPayments::normalizeCurrency($this->workspaceCurrency);
        $validated = $this->validate([
            'workspaceCurrency' => ['required', 'string', Rule::in(UpcomingPayments::CURRENCIES)],
        ]);
        $this->workspace()->update(['currency' => $validated['workspaceCurrency']]);
        $this->toast(__('app.flash.workspace_saved'));
    }

    public function sendTestMail(): void
    {
        $email = auth()->user()->email;

        try {
            Mail::raw(__('app.account.smtp_body'), function ($message) use ($email) {
                $message->to($email)->subject(__('app.account.smtp_subject'));
            });
            $this->toast(__('app.flash.smtp_sent', ['email' => $email]));
        } catch (\Throwable $e) {
            report($e);
            $this->addError('smtp', __('app.flash.smtp_failed'));
        }
    }

    public function sendVerification(): void
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        RateLimits::hitOrFail('verify:'.$user->id);

        try {
            $user->sendEmailVerificationNotification();
            $this->toast(__('app.flash.verification_sent'));
        } catch (\Throwable $e) {
            report($e);
            $this->addError('verification', __('app.flash.smtp_failed'));
        }
    }

    public function deleteAccount(AccountDeleter $deleter)
    {
        $this->validate([
            'deletePassword' => ['required', 'current_password'],
        ]);

        $user = auth()->user();
        $deleter->delete($user);

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirect(route('home'));
    }

    public function render()
    {
        return view('livewire.settings.account', [
            'user' => auth()->user(),
            'isOwner' => auth()->user()->ownsCurrentWorkspace(),
            'currencies' => UpcomingPayments::CURRENCIES,
        ])->title(__('app.titles.account'));
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\AdminUser;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class SelfHostEditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_host_landing_has_no_waitlist(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Join waitlist')
            ->assertSee('Self-hosted edition');
    }

    public function test_self_host_rejects_waitlist_and_simulate(): void
    {
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Studio', $user);

        $this->post(route('waitlist.store'), [
            'email' => 'agency@example.com',
        ])->assertNotFound();

        $this->actingAs($user)
            ->get(route('billing.simulate', ['plan' => 'agency', 'workspace' => $user->current_workspace_id]))
            ->assertNotFound();
    }

    public function test_self_host_admin_hides_cloud_resources(): void
    {
        $this->seed();
        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->first();

        $this->actingAs($admin, 'admin')
            ->get('/admin/payment-events')
            ->assertForbidden();

        $this->actingAs($admin, 'admin')
            ->get('/admin/subscriptions')
            ->assertForbidden();
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@studio.test']);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('send')
            ->assertSee('Если такой email есть');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'newpassword12')
            ->set('password_confirmation', 'newpassword12')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newpassword12', $user->fresh()->password));
    }
}

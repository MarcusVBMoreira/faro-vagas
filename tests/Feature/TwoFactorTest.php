<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function userWith2fa(): User
    {
        return User::factory()->create([
            'email' => 'ana@exemplo.com',
            'password' => 'senha1234',
            'two_factor_email' => true,
        ]);
    }

    public function test_login_with_2fa_requires_the_emailed_code(): void
    {
        Notification::fake();
        $user = $this->userWith2fa();

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertRedirect(route('two-factor.show'));
        $this->assertGuest(); // senha certa ainda não basta

        $code = null;
        Notification::assertSentTo($user, EmailCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return $n->purpose === 'login';
        });

        $this->get('/entrar/codigo')->assertOk()->assertSee('an•');

        $this->post('/entrar/codigo', ['code' => $code])->assertRedirect(route('profile.show'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_2fa_code_keeps_user_logged_out(): void
    {
        Notification::fake();
        $this->userWith2fa();

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234']);
        $this->post('/entrar/codigo', ['code' => '000000'])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_pending_2fa_login_expires(): void
    {
        Notification::fake();
        $this->userWith2fa();

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234']);
        $this->travel(16)->minutes();

        $this->get('/entrar/codigo')->assertRedirect(route('login'));
        $this->post('/entrar/codigo', ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_code_page_without_pending_login_goes_back_to_login(): void
    {
        $this->get('/entrar/codigo')->assertRedirect(route('login'));
    }

    public function test_wrong_password_never_sends_a_code(): void
    {
        Notification::fake();
        $this->userWith2fa();

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'errada123'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_user_can_turn_2fa_on_with_current_password(): void
    {
        $user = User::factory()->create(['password' => 'senha1234']);

        $this->actingAs($user)->put('/conta/seguranca', ['two_factor_email' => '1', 'current_password' => 'errada'])
            ->assertSessionHasErrors('current_password');
        $this->assertFalse((bool) $user->fresh()->two_factor_email);

        $this->actingAs($user)->put('/conta/seguranca', ['two_factor_email' => '1', 'current_password' => 'senha1234'])
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $user->fresh()->two_factor_email);
    }

    public function test_user_can_change_password_in_security_settings(): void
    {
        $user = User::factory()->create(['password' => 'senha1234']);

        $this->actingAs($user)->put('/conta/seguranca', [
            'current_password' => 'senha1234',
            'password' => 'novaSenha99',
            'password_confirmation' => 'novaSenha99',
        ])->assertSessionHasNoErrors();

        $this->post('/sair');
        $this->post('/entrar', ['email' => $user->email, 'password' => 'novaSenha99']);
        $this->assertAuthenticatedAs($user);
    }

    private function loginWithCode(array $extra = [], array $loginExtra = []): \Illuminate\Testing\TestResponse
    {
        Notification::fake();
        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234', ...$loginExtra]);

        $code = null;
        Notification::assertSentTo(User::firstWhere('email', 'ana@exemplo.com'), EmailCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $this->post('/entrar/codigo', ['code' => $code, ...$extra]);
    }

    public function test_keep_me_signed_in_works_with_2fa(): void
    {
        $this->userWith2fa();

        $response = $this->loginWithCode(['trust_device' => '1'], ['remember' => '1']);

        $response->assertRedirect(route('profile.show'));
        $response->assertCookie(\Illuminate\Support\Facades\Auth::guard('web')->getRecallerName());
        $response->assertCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE);
    }

    public function test_code_page_pre_checks_trust_option_when_remember_was_ticked(): void
    {
        Notification::fake();
        $this->userWith2fa();

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234', 'remember' => '1']);

        $this->get('/entrar/codigo')->assertSee('name="trust_device" value="1"', false)->assertSee('checked', false);
    }

    public function test_trusted_browser_skips_the_code_next_time(): void
    {
        $user = $this->userWith2fa();
        $trust = $this->loginWithCode(['trust_device' => '1'])
            ->getCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE)->getValue();

        $this->post('/sair');
        Notification::fake();

        $this->withCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE, $trust)
            ->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertRedirect(route('profile.show'));

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_without_trust_the_code_is_asked_again(): void
    {
        $this->userWith2fa();
        $this->loginWithCode()->assertCookieMissing(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE);
        $this->post('/sair');

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertRedirect(route('two-factor.show'));
    }

    public function test_changing_password_revokes_trusted_browsers(): void
    {
        $user = $this->userWith2fa();
        $trust = $this->loginWithCode(['trust_device' => '1'])
            ->getCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE)->getValue();
        $this->post('/sair');

        $user->update(['password' => 'outraSenha99']);

        Notification::fake();
        $this->withCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE, $trust)
            ->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'outraSenha99'])
            ->assertRedirect(route('two-factor.show'));

        // e um cookie forjado para outro usuário também não vale
        $this->withCookie(\App\Http\Controllers\Auth\TwoFactorController::TRUST_COOKIE, $user->id.'|forjado')
            ->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'outraSenha99'])
            ->assertRedirect(route('two-factor.show'));
    }
}

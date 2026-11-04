<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_pages_render(): void
    {
        $this->get('/cadastro')->assertOk()->assertSee('Criar conta');
        $this->get('/entrar')->assertOk()->assertSee('Entrar');
        $this->get('/esqueci-a-senha')->assertOk()->assertSee('Esqueci a senha');
        $this->get('/privacidade')->assertOk()->assertSee('Política de privacidade');
    }

    public function test_password_fields_have_a_show_password_button(): void
    {
        foreach (['/entrar', '/cadastro'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('aria-label="Mostrar senha"', false)
                ->assertSee('aria-controls="password"', false);
        }
        $this->get('/cadastro')->assertSee('aria-controls="password_confirmation"', false);

        $this->get('/idioma/en');
        $this->get('/entrar')->assertSee('aria-label="Show password"', false);
    }

    public function test_user_can_register_and_gets_an_empty_profile(): void
    {
        Notification::fake();

        $response = $this->post('/cadastro', [
            'name' => 'Ana Souza',
            'email' => 'Ana@Exemplo.com ',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::first();
        $this->assertSame('ana@exemplo.com', $user->email);
        $this->assertSame('pt_BR', $user->locale);
        $this->assertNotNull($user->profile);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentToTimes($user, \App\Notifications\EmailCodeNotification::class, 1);
    }

    public function test_register_requires_terms_and_strong_password(): void
    {
        $this->post('/cadastro', [
            'name' => 'Ana',
            'email' => 'ana@exemplo.com',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertSessionHasErrors(['password', 'terms']);

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'ana@exemplo.com', 'password' => 'senha1234']);

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertRedirect(route('profile.show'));
        $this->assertAuthenticatedAs($user);

        $this->post('/sair')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_wrong_password_shows_error_in_portuguese(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.com', 'password' => 'senha1234']);

        $this->from('/entrar')
            ->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'errada123'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'ana@exemplo.com', 'password' => 'senha1234']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'errada123']);
        }

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/perfil')->assertRedirect(route('login'));
    }

    public function test_password_can_be_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ana@exemplo.com']);

        $this->post('/esqueci-a-senha', ['email' => 'ana@exemplo.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get('/redefinir-senha/'.$notification->token.'?email='.$user->email)->assertOk();

            $this->post('/redefinir-senha', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'novaSenha123',
                'password_confirmation' => 'novaSenha123',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'novaSenha123']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->post('/esqueci-a-senha', ['email' => 'ninguem@exemplo.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }
}

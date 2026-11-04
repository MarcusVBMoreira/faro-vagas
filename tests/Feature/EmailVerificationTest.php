<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailCodeNotification;
use App\Services\Auth\EmailCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(): User
    {
        $this->post('/cadastro', [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'terms' => '1',
        ]);

        return User::firstWhere('email', 'ana@exemplo.com');
    }

    private function sentCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, EmailCodeNotification::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_signup_sends_a_six_digit_code_and_the_code_verifies_the_email(): void
    {
        Notification::fake();
        $user = $this->register();
        $code = $this->sentCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertDatabaseMissing('verification_codes', ['code_hash' => $code]); // nunca em texto puro

        $this->get('/verificar-email')->assertOk()->assertSee('Confirme seu e-mail');
        Notification::assertSentToTimes($user, EmailCodeNotification::class, 1); // a página não reenvia se já há código

        $this->post('/verificar-email', ['code' => substr($code, 0, 3).' '.substr($code, 3)])
            ->assertRedirect(route('profile.show'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/perfil')->assertOk();
    }

    public function test_wrong_code_is_rejected_and_attempts_are_limited(): void
    {
        Notification::fake();
        $user = $this->register();
        $code = $this->sentCode($user);
        $wrong = $code === '111111' ? '222222' : '111111';

        for ($i = 0; $i < EmailCodeService::MAX_ATTEMPTS; $i++) {
            $this->post('/verificar-email', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        // Depois de 5 erros, nem o código certo vale mais.
        $this->post('/verificar-email', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_expires(): void
    {
        Notification::fake();
        $user = $this->register();
        $code = $this->sentCode($user);

        $this->travel(EmailCodeService::TTL_MINUTES + 1)->minutes();

        $this->post('/verificar-email', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_invalidates_old_code_and_is_throttled(): void
    {
        Notification::fake();
        $user = $this->register();
        $old = $this->sentCode($user);

        $this->post('/verificar-email/reenviar')->assertSessionHas('status');
        $this->post('/verificar-email/reenviar')->assertSessionHasErrors('code'); // 1 por minuto
        Notification::assertSentToTimes($user, EmailCodeNotification::class, 2);

        $codes = [];
        Notification::assertSentTo($user, EmailCodeNotification::class, function ($n) use (&$codes) {
            $codes[] = $n->code;

            return true;
        });
        $new = end($codes);

        if ($old !== $new) {
            $this->post('/verificar-email', ['code' => $old])->assertSessionHasErrors('code');
        }
        $this->post('/verificar-email', ['code' => $new])->assertRedirect(route('profile.show'));
    }

    public function test_existing_unverified_account_gets_a_code_when_opening_the_page(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verificar-email')->assertOk();

        Notification::assertSentToTimes($user, EmailCodeNotification::class, 1);
    }

    public function test_verified_user_skips_the_verification_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verificar-email')->assertRedirect(route('profile.show'));
    }

    public function test_code_email_is_sent_in_the_users_language(): void
    {
        $user = User::factory()->create(['name' => 'Ana Souza', 'locale' => 'en']);

        $this->assertSame('en', $user->preferredLocale());

        app()->setLocale('en');
        $mail = (new EmailCodeNotification('123456', EmailCodeService::VERIFY_EMAIL))->toMail($user);
        $this->assertSame('Confirm your email on Faro: 123456', $mail->subject);
        $this->assertSame('Hi, Ana!', $mail->greeting);
    }

    private function breakSmtp(): void
    {
        // SMTP que recusa a conexão: o mesmo efeito de senha errada ou servidor fora do ar.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2,
        ]);
    }

    public function test_mail_failure_shows_a_warning_instead_of_a_server_error(): void
    {
        $this->breakSmtp();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verificar-email')
            ->assertOk()
            ->assertSee('Não conseguimos enviar o e-mail agora.');

        $this->assertDatabaseCount('verification_codes', 0); // código não entregue não fica valendo

        $this->actingAs($user)->post('/verificar-email/reenviar')->assertSessionHasErrors('code');
    }

    public function test_signup_still_works_when_mail_is_down(): void
    {
        $this->breakSmtp();

        $this->post('/cadastro', [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $this->get('/verificar-email')->assertOk()->assertSee('Não conseguimos enviar o e-mail agora.');
    }

    public function test_2fa_login_and_password_reset_survive_mail_failure(): void
    {
        $this->breakSmtp();
        User::factory()->create(['email' => 'ana@exemplo.com', 'password' => 'senha1234', 'two_factor_email' => true]);

        $this->from('/entrar')->post('/entrar', ['email' => 'ana@exemplo.com', 'password' => 'senha1234'])
            ->assertRedirect('/entrar')
            ->assertSessionHasErrors(['email' => 'Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.']);
        $this->assertGuest();

        $this->from('/esqueci-a-senha')->post('/esqueci-a-senha', ['email' => 'ana@exemplo.com'])
            ->assertRedirect('/esqueci-a-senha')
            ->assertSessionHasErrors('email');
    }
}

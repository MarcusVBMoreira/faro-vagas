<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_is_portuguese(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="pt-BR"', false)->assertSee('Pare de procurar vaga.');
    }

    public function test_visitor_can_switch_to_english_and_spanish(): void
    {
        $this->get('/idioma/en')->assertRedirect();
        $this->get('/')->assertSee('lang="en"', false)->assertSee('Stop searching for jobs.')->assertDontSee('Pare de procurar vaga.');

        $this->get('/idioma/es');
        $this->get('/entrar')->assertSee('Iniciar sesión');
    }

    public function test_browser_language_is_used_on_first_visit(): void
    {
        $this->withHeader('Accept-Language', 'es-AR,es;q=0.9')->get('/')->assertSee('Deja de buscar empleo.');
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->get('/idioma/xx')->assertSessionHasErrors('locale');
        $this->get('/')->assertSee('Pare de procurar vaga.');
    }

    public function test_choice_is_saved_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/idioma/es');

        $this->assertSame('es', $user->fresh()->locale);
    }

    public function test_validation_messages_follow_the_language(): void
    {
        $this->get('/idioma/en');
        $this->post('/entrar', ['email' => 'nao-e-email', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);
    }

    public function test_profile_page_renders_in_every_language(): void
    {
        $user = User::factory()->create();

        foreach (['en' => 'Career goal', 'es' => 'Objetivo profesional', 'pt_BR' => 'Objetivo profissional'] as $locale => $text) {
            $user->update(['locale' => $locale]);
            $this->flushSession();
            $this->actingAs($user->fresh())->get('/perfil')->assertOk()->assertSee($text);
        }
    }
}

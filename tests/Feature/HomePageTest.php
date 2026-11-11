<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_the_brand(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Faro')
            ->assertSee('A IA que encontra as vagas certas para você');
    }

    public function test_top_menu_is_fixed_on_every_layout(): void
    {
        // Na home o menu precisa ficar fora do bloco de destaque, senão para de acompanhar a rolagem.
        $html = $this->get('/')->assertSee('sticky top-0', false)->getContent();
        $this->assertLessThan(strpos($html, 'overflow-clip'), strpos($html, 'sticky top-0'));
        $this->get('/privacidade')->assertSee('sticky top-0', false);
        $this->get('/entrar')->assertSee('sticky top-0', false);
        $this->actingAs(\App\Models\User::factory()->create())->get('/perfil')->assertSee('sticky top-0', false);
    }

    public function test_loading_screen_is_on_every_page(): void
    {
        $this->get('/')->assertSee('id="page-loading"', false);
        $this->get('/entrar')->assertSee('id="page-loading"', false);

        $this->actingAs(\App\Models\User::factory()->create())->get('/perfil')
            ->assertSee('id="page-loading"', false)
            ->assertSee('data-loading="Salvando perfil…"', false)
            ->assertSee('this.form.requestSubmit()', false); // submit() não dispara o evento que mostra a tela
    }

    public function test_health_check_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_home_shows_live_numbers_and_finished_features(): void
    {
        \App\Models\JobPosting::factory()->count(3)->create();
        \App\Models\JobPosting::factory()->create(['status' => 'closed']);

        $this->get('/')
            ->assertOk()
            ->assertSee('3 vagas ativas agora')
            ->assertSee('Quadro de candidaturas')
            ->assertSee('Match com nota, pontos fortes e o que falta')
            ->assertDontSee('Semana 2')
            ->assertDontSee('Beta');
    }
}

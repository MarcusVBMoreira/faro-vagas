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

    public function test_health_check_is_up(): void
    {
        $this->get('/up')->assertOk();
    }
}

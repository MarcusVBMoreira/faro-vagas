<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_shows_the_brand(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Faro')
            ->assertSee('A IA que encontra as vagas certas para você');
    }

    public function test_health_check_is_up(): void
    {
        $this->get('/up')->assertOk();
    }
}

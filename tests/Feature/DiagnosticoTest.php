<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiagnosticoTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostico_reports_without_secrets(): void
    {
        Http::fake(['ia.test/api/tags' => Http::response(['models' => [['name' => 'qwen2.5:3b'], ['name' => 'bge-m3:latest']]])]);

        $this->artisan('faro:diagnostico')
            ->expectsOutputToContain('HTTP 200')
            ->doesntExpectOutputToContain('token-de-teste')
            ->assertSuccessful();
    }
}

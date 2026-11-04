<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // O Symfony simula um navegador em inglês (Accept-Language: en-us) por padrão.
        // Os testes partem de um navegador brasileiro, como o público do Faro.
        $this->withHeader('Accept-Language', 'pt-BR,pt;q=0.9');
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // O Symfony simula um navegador em inglês (Accept-Language: en-us) por padrão.
        // Os testes partem de um navegador brasileiro, como o público do Faro.
        $this->withHeader('Accept-Language', 'pt-BR,pt;q=0.9');

        // Embeddings falsos e determinísticos para o servidor de IA dos testes:
        // textos com palavras em comum ficam parecidos, como num modelo de verdade.
        Http::fake([
            'ia.test/api/embed' => fn (Request $request) => Http::response([
                'embeddings' => array_map([self::class, 'fakeEmbedding'], (array) $request['input']),
                'prompt_eval_count' => 10,
            ]),
        ]);
    }

    /** "Saco de palavras" com hashing em 64 dimensões. */
    public static function fakeEmbedding(string $text): array
    {
        $vector = array_fill(0, 64, 0.0);
        foreach (preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($text))) ?: [] as $word) {
            if (mb_strlen($word) >= 3) {
                $vector[crc32($word) % 64] += 1.0;
            }
        }
        $vector[63] += 0.01; // nunca vetor zero

        return $vector;
    }
}

<?php

namespace App\Contracts;

use App\Models\User;

/**
 * O que o Faro precisa de um provedor de IA. Hoje é o Ollama no VPS do projeto
 * (App\Services\Ai\OllamaClient); trocar de provedor é implementar esta interface.
 */
interface AiClient
{
    /**
     * Gera uma resposta em JSON seguindo o schema informado.
     *
     * @param  array<string, mixed>  $schema  JSON Schema da resposta
     * @return array<string, mixed>
     *
     * @throws \App\Services\Ai\AiException
     */
    public function generateJson(string $prompt, array $schema, string $operation, ?User $user = null): array;

    /**
     * Transforma textos em vetores (embeddings).
     *
     * @param  list<string>  $texts
     * @return list<list<float>>
     *
     * @throws \App\Services\Ai\AiException
     */
    public function embed(array $texts, string $operation, ?User $user = null): array;
}

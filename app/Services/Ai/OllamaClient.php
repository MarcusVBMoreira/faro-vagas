<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente do Ollama que roda no VPS do projeto, atrás de nginx com HTTPS e token.
 */
class OllamaClient implements AiClient
{
    public function generateJson(string $prompt, array $schema, string $operation, ?User $user = null): array
    {
        $this->guardDailyLimit($user);

        $model = config('faro.ai.model');

        $response = $this->send('/api/generate', [
            'model' => $model,
            'prompt' => $prompt,
            'format' => $schema,
            'stream' => false,
            'keep_alive' => '10m',
            'options' => [
                'temperature' => 0,
                'num_ctx' => config('faro.ai.context_window'),
            ],
        ]);

        $this->logUsage($user, $operation, $model, $response);

        $decoded = json_decode((string) $response->json('response'), true);

        if (! is_array($decoded)) {
            throw AiException::invalidResponse('o modelo não devolveu um JSON válido.');
        }

        return $decoded;
    }

    public function embed(array $texts, string $operation, ?User $user = null): array
    {
        $this->guardDailyLimit($user);

        $model = config('faro.ai.embedding_model');

        $response = $this->send('/api/embed', [
            'model' => $model,
            'input' => array_values($texts),
            'keep_alive' => '10m',
        ]);

        $this->logUsage($user, $operation, $model, $response);

        $embeddings = $response->json('embeddings');

        if (! is_array($embeddings) || count($embeddings) !== count($texts)) {
            throw AiException::invalidResponse('quantidade de embeddings diferente da de textos.');
        }

        return $embeddings;
    }

    private function send(string $path, array $payload): Response
    {
        if (blank(config('faro.ai.api_key'))) {
            throw AiException::unavailable('AI_API_KEY não configurada.');
        }

        try {
            $response = $this->http()->post($path, $payload);
        } catch (ConnectionException $e) {
            throw AiException::unavailable('sem conexão com o servidor de IA.');
        }

        if ($response->failed()) {
            throw AiException::unavailable("servidor respondeu HTTP {$response->status()}.");
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('faro.ai.base_url'))
            ->withToken((string) config('faro.ai.api_key'))
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(config('faro.ai.timeout'))
            ->retry(2, 2000, fn ($exception) => $exception instanceof ConnectionException, throw: false);
    }

    private function guardDailyLimit(?User $user): void
    {
        if (! $user) {
            return;
        }

        $usedToday = AiUsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($usedToday >= config('faro.ai.daily_limit_per_user')) {
            throw AiException::dailyLimitReached();
        }
    }

    private function logUsage(?User $user, string $operation, string $model, Response $response): void
    {
        AiUsageLog::create([
            'user_id' => $user?->id,
            'operation' => $operation,
            'model' => $model,
            'input_tokens' => (int) $response->json('prompt_eval_count', 0),
            'output_tokens' => (int) $response->json('eval_count', 0),
        ]);
    }
}

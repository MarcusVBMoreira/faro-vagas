<?php

namespace App\Services\Ai;

use RuntimeException;

class AiException extends RuntimeException
{
    public static function unavailable(string $detail): self
    {
        return new self("IA indisponível: {$detail}");
    }

    public static function invalidResponse(string $detail): self
    {
        return new self("Resposta inválida da IA: {$detail}");
    }

    public static function dailyLimitReached(): self
    {
        return new self('Limite diário de uso da IA atingido.');
    }
}

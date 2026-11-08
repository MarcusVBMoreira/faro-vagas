<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Matching\MatchService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recalcula as "vagas para você" de uma pessoa. Disparado quando o perfil muda
 * e de hora em hora (vagas novas). Único por usuário: vários "salvar" seguidos viram um cálculo.
 */
class ComputeMatches implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $uniqueFor = 600;

    public function __construct(public User $user)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->user->id;
    }

    public function handle(MatchService $matches): void
    {
        $matches->computeFor($this->user);
    }
}

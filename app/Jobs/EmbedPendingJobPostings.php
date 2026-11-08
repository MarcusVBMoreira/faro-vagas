<?php

namespace App\Jobs;

use App\Contracts\AiClient;
use App\Models\JobPosting;
use App\Services\Ai\AiException;
use App\Services\Matching\EmbeddingText;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Gera os embeddings das vagas ativas que ainda não têm (em lotes, na fila "jobs").
 * Espera a IA ler a vaga (extracted_at) para o texto já ter área e habilidades;
 * vaga que a IA não conseguiu ler em 6 horas entra mesmo assim.
 */
class EmbedPendingJobPostings implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const BATCH = 8;

    public const PER_RUN = 48;

    public int $timeout = 280;

    public int $uniqueFor = 900;

    public function handle(AiClient $ai): void
    {
        $jobs = JobPosting::active()
            ->whereNull('embedded_at')
            ->where(fn ($q) => $q->whereNotNull('extracted_at')->orWhere('created_at', '<', now()->subHours(6)))
            ->with('skills')
            ->orderBy('id')
            ->limit(self::PER_RUN)
            ->get();

        foreach ($jobs->chunk(self::BATCH) as $batch) {
            try {
                $vectors = $ai->embed($batch->map(fn ($j) => EmbeddingText::forJob($j))->values()->all(), 'embed_job');
            } catch (AiException) {
                return; // tenta de novo no próximo agendamento
            }

            foreach ($batch->values() as $i => $job) {
                $job->forceFill(['embedding' => $vectors[$i], 'embedded_at' => now()])->save();
            }
        }
    }
}

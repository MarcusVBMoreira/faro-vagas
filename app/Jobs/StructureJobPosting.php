<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Services\Ai\AiException;
use App\Services\Jobs\JobAnalyzer;
use App\Services\SkillCatalog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Completa uma vaga com a IA (fila "jobs", de prioridade menor que a análise de currículos).
 * O que a classificação rápida já descobriu não é sobrescrito.
 * (A propriedade se chama $posting porque $job já existe no trait Queueable.)
 */
class StructureJobPosting implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 120;

    public function __construct(public JobPosting $posting)
    {
    }

    public function handle(JobAnalyzer $analyzer, SkillCatalog $catalog): void
    {
        $job = $this->posting->fresh();
        if (! $job || $job->extracted_at || $job->status !== 'active') {
            return;
        }

        try {
            $result = $analyzer->analyze($job);
        } catch (AiException $e) {
            Log::warning('IA indisponível ao estruturar vaga', ['job_posting_id' => $job->id, 'erro' => $e->getMessage()]);
            $this->release($this->backoff);

            return;
        }

        $fields = $result['fields'];
        // Só preenche o que está vazio (a área "outros" conta como vazia)
        foreach (['level', 'education_level', 'area'] as $field) {
            $current = $job->getRawOriginal($field);
            if ($current !== null && ! ($field === 'area' && $current === 'outros')) {
                unset($fields[$field]);
            }
        }

        $job->fill([...$fields, 'extracted_at' => now()])->save();

        $sync = [];
        foreach ($catalog->resolve($result['desirable']) as $skill) {
            $sync[$skill->id] = ['is_required' => false];
        }
        foreach ($catalog->resolve($result['required']) as $skill) {
            $sync[$skill->id] = ['is_required' => true];
        }
        $job->skills()->sync($sync);
    }
}

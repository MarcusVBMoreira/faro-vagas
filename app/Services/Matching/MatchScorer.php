<?php

namespace App\Services\Matching;

use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;

/**
 * Nota de compatibilidade 0–100, calculada em código (explicável e sem alucinação).
 *
 *   Habilidades ........ 40  (obrigatórias 30 + diferenciais 10)
 *   Similaridade (IA) .. 30  (embeddings do perfil e da vaga)
 *   Área ............... 15
 *   Nível .............. 10
 *   Modelo e contrato ..  5
 *
 * Item sem informação (vaga sem habilidades, sem nível, ainda sem embedding...) vale 40% do
 * seu peso: não zera a vaga mal descrita, mas também não deixa uma vaga quase vazia chegar
 * ao topo só porque o pouco que se sabe dela bate com o perfil.
 * Escolaridade abaixo da pedida tira 10 pontos. Dado de PcD nunca entra na conta.
 */
final class MatchScorer
{
    public const WEIGHTS = ['skills' => 40, 'similarity' => 30, 'area' => 15, 'level' => 10, 'logistics' => 5];

    /** Faixa típica de cosseno do bge-m3 entre textos de vaga: abaixo de MIN é "nada a ver", acima de MAX é "muito parecido". */
    private const SIM_MIN = 0.35;

    private const SIM_MAX = 0.80;

    /** Crédito de um item desconhecido (fração do peso). */
    public const UNKNOWN_CREDIT = 0.4;

    /**
     * @param  array<int, true>  $profileSkillIds
     * @return array{score: int, strengths: list<array{k: string, v?: string}>, gaps: list<array{k: string, v?: string}>}
     */
    public function score(Profile $profile, array $profileSkillIds, JobPosting $job, ?float $similarity): array
    {
        $parts = [];       // nome => fração 0..1 (null = desconhecido)
        $strengths = [];
        $gaps = [];

        // Habilidades
        $required = $job->skills->filter(fn ($s) => (bool) $s->pivot->is_required);
        $desirable = $job->skills->reject(fn ($s) => (bool) $s->pivot->is_required);
        if ($job->skills->isNotEmpty()) {
            $haveReq = $required->filter(fn ($s) => isset($profileSkillIds[$s->id]));
            $haveDes = $desirable->filter(fn ($s) => isset($profileSkillIds[$s->id]));
            $req = $required->count() ? $haveReq->count() / $required->count() : null;
            $des = $desirable->count() ? $haveDes->count() / $desirable->count() : null;
            $parts['skills'] = match (true) {
                $req !== null && $des !== null => 0.75 * $req + 0.25 * $des,
                default => $req ?? $des,
            };

            foreach ($haveReq->concat($haveDes) as $s) {
                $strengths[] = ['k' => 'skill', 'v' => $s->name];
            }
            foreach ($required->reject(fn ($s) => isset($profileSkillIds[$s->id]))->take(6) as $s) {
                $gaps[] = ['k' => 'skill', 'v' => $s->name];
            }
            foreach ($desirable->reject(fn ($s) => isset($profileSkillIds[$s->id]))->take(3) as $s) {
                $gaps[] = ['k' => 'nice', 'v' => $s->name];
            }
        } else {
            $parts['skills'] = null;
        }

        // Similaridade semântica
        $parts['similarity'] = $similarity === null ? null
            : max(0.0, min(1.0, ($similarity - self::SIM_MIN) / (self::SIM_MAX - self::SIM_MIN)));

        // Área
        if ($job->area && $profile->area) {
            $parts['area'] = $job->area === $profile->area ? 1.0 : 0.0;
            if ($job->area === $profile->area) {
                $strengths[] = ['k' => 'area'];
            }
        } else {
            $parts['area'] = null;
        }

        // Nível (distância na escala de JobLevel)
        if ($job->level && $profile->desired_level) {
            $distance = abs($this->levelIndex($job->level) - $this->levelIndex($profile->desired_level));
            $parts['level'] = match (true) {
                $distance === 0 => 1.0,
                $distance === 1 => 0.6,
                $distance === 2 => 0.25,
                default => 0.0,
            };
            if ($distance === 0) {
                $strengths[] = ['k' => 'level'];
            } elseif ($distance >= 2) {
                $gaps[] = ['k' => 'level', 'v' => $job->level->value];
            }
        } else {
            $parts['level'] = null;
        }

        // Modelo de trabalho e contrato
        $logistics = [];
        if ($job->modality && $profile->desired_modalities) {
            $ok = in_array($job->modality->value, $profile->desired_modalities, true);
            $logistics[] = $ok ? 1.0 : 0.0;
            $ok ? $strengths[] = ['k' => 'modality'] : $gaps[] = ['k' => 'modality', 'v' => $job->modality->value];
        }
        if ($job->contract_type && $profile->desired_contracts) {
            $ok = in_array($job->contract_type, $profile->desired_contracts, true);
            $logistics[] = $ok ? 1.0 : 0.0;
            if (! $ok) {
                $gaps[] = ['k' => 'contract', 'v' => $job->contract_type];
            }
        }
        $parts['logistics'] = $logistics ? array_sum($logistics) / count($logistics) : null;

        // Soma ponderada (pesos somam 100); desconhecido vale UNKNOWN_CREDIT
        $score = 0.0;
        foreach (self::WEIGHTS as $name => $weight) {
            $score += ($parts[$name] ?? self::UNKNOWN_CREDIT) * $weight;
        }

        // Escolaridade abaixo da pedida
        if ($job->education_level && $profile->education_level && $profile->education_level->rank() < $job->education_level->rank()) {
            $score -= 10;
            $gaps[] = ['k' => 'education', 'v' => $job->education_level->value];
        }

        return [
            'score' => (int) round(max(0, min(100, $score))),
            'strengths' => $strengths,
            'gaps' => $gaps,
        ];
    }

    private function levelIndex(JobLevel $level): int
    {
        return array_search($level, JobLevel::cases(), true);
    }
}

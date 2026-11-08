<?php

namespace App\Services\Matching;

use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Support\Grounding;
use Illuminate\Support\Str;

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

    /**
     * Faixa de cosseno do bge-m3 entre perfil e vagas, medida em produção (nov/2026, 711 vagas):
     * mínimo 0,38 · mediana 0,52 · p90 0,61 · máximo 0,76. Abaixo de MIN é "nada a ver";
     * a partir de MAX, "muito parecido".
     */
    private const SIM_MIN = 0.45;

    private const SIM_MAX = 0.70;

    /** Requisito que na verdade é formação ("Ciência da Computação", "cursando TI"). */
    private const EDUCATION_SKILL = '/(ciencias? da computacao|sistemas? de informacao|gestao da tecnologia|formacao|graduac|cursando|ensino superior|tecnologo|faculdade|bacharel|licenciatura|curso tecnico)/';

    /** Crédito de um item desconhecido (fração do peso). */
    public const UNKNOWN_CREDIT = 0.4;

    /**
     * @param  array<int, true>  $profileSkillIds
     * @param  string|null  $evidence  texto do perfil já passado por Grounding::plain() (habilidades,
     *                                 experiências, formação, idiomas): "Lógica de programação" bate
     *                                 com quem tem "programação"; "Node.js" com quem tem "Node".
     * @return array{score: int, strengths: list<array{k: string, v?: string}>, gaps: list<array{k: string, v?: string}>, parts: array<string, ?float>}
     */
    public function score(Profile $profile, array $profileSkillIds, JobPosting $job, ?float $similarity, ?string $evidence = null): array
    {
        $has = function ($skill) use ($profile, $profileSkillIds, $evidence, $job): bool {
            if (isset($profileSkillIds[$skill->id])) {
                return true;
            }
            $name = Str::lower(Str::ascii(str_replace('_', ' ', $skill->name)));
            if (preg_match(self::EDUCATION_SKILL, $name)) {
                // Pede formação: atende quem estuda ou já se formou, na mesma área da vaga
                return (CareerFit::isStudent($profile) || ($profile->education_level?->rank() ?? 0) >= EducationLevel::Superior->rank())
                    && (! $job->area || $job->area === $profile->area);
            }

            return $evidence !== null && Grounding::isGrounded($name, $evidence, 0.5);
        };

        $parts = [];       // nome => fração 0..1 (null = desconhecido)
        $strengths = [];
        $gaps = [];

        // Habilidades ("PcD" listado como requisito é ignorado: dado sensível não entra na nota)
        $skills = $job->skills->reject(fn ($s) => Str::lower(trim($s->name)) === 'pcd');
        $required = $skills->filter(fn ($s) => (bool) $s->pivot->is_required);
        $desirable = $skills->reject(fn ($s) => (bool) $s->pivot->is_required);
        if ($skills->isNotEmpty()) {
            $haveReq = $required->filter($has);
            $haveDes = $desirable->filter($has);
            $req = $required->count() ? $haveReq->count() / $required->count() : null;
            $des = $desirable->count() ? $haveDes->count() / $desirable->count() : null;
            $parts['skills'] = match (true) {
                $req !== null && $des !== null => 0.75 * $req + 0.25 * $des,
                default => $req ?? $des,
            };

            foreach ($haveReq->concat($haveDes) as $s) {
                $strengths[] = ['k' => 'skill', 'v' => $s->name];
            }
            foreach ($required->diff($haveReq)->take(6) as $s) {
                $gaps[] = ['k' => 'skill', 'v' => $s->name];
            }
            foreach ($desirable->diff($haveDes)->take(3) as $s) {
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

        // Nível: o desejado vale tudo; os outros níveis aceitos (CareerFit) valem 80%
        $accepted = CareerFit::acceptedLevels($profile);
        if ($job->level && ($profile->desired_level || $accepted)) {
            $exact = $job->level === $profile->desired_level;
            $ok = $exact || in_array($job->level->value, $accepted ?? [], true);
            $distance = $profile->desired_level ? abs($this->levelIndex($job->level) - $this->levelIndex($profile->desired_level)) : 3;
            $parts['level'] = match (true) {
                $exact => 1.0,
                $ok => 0.8,
                $distance === 1 => 0.5,
                default => 0.0,
            };
            if ($ok) {
                $strengths[] = ['k' => 'level'];
            } else {
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
            'parts' => $parts, // frações 0..1 por critério (null = desconhecido), para diagnóstico
        ];
    }

    private function levelIndex(JobLevel $level): int
    {
        return array_search($level, JobLevel::cases(), true);
    }
}

<?php

namespace App\Services\Jobs;

use App\Enums\WorkModality;
use App\Support\ProfileNormalizer;
use Illuminate\Support\Str;

/**
 * Classificação rápida e sem IA, a partir do título, da categoria e do começo da descrição.
 * A IA (StructureJobPosting) completa depois só o que ficou em branco: área, nível,
 * escolaridade mínima e habilidades.
 *
 * @return array<string, mixed> campos de job_postings
 */
class JobClassifier
{
    public function classify(JobData $job): array
    {
        $title = $this->plain($job->title);
        $head = $this->plain(mb_substr($job->description, 0, 2500));
        $all = $title.' '.$head.' '.$this->plain(implode(' ', $job->tags));

        return [
            'level' => ProfileNormalizer::level($job->title)?->value ?? $this->levelFromText($all),
            'area' => $this->area($job),
            'modality' => $this->modality($job, $all)?->value,
            'contract_type' => $job->contractType ?? $this->contract($title, $all),
            'is_pcd' => (bool) preg_match('/\b(pcd|pessoas? com deficiencia)\b.{0,40}\b(exclusiv|afirmativ|somente|apenas)|\b(exclusiv|afirmativ)\w*\b.{0,40}\b(pcd|pessoas? com deficiencia)\b|^\W*pcd\b|\bvaga pcd\b|\(pcd\)/u', $title.' '.$head),
        ];
    }

    private function area(JobData $job): ?string
    {
        // A categoria da fonte ("Vendas", "Logística", "Saúde e Enfermagem") é a pista mais confiável
        foreach ([$job->category, $job->title] as $hint) {
            if (filled($hint)) {
                $area = ProfileNormalizer::area($hint);
                if ($area && $area !== 'outros') {
                    return $area;
                }
            }
        }

        return null;
    }

    private function modality(JobData $job, string $text): ?WorkModality
    {
        return match (true) {
            str_contains($text, 'hibrid') || str_contains($text, 'hybrid') => WorkModality::Hibrido,
            $job->remote => WorkModality::Remoto,
            str_contains($text, 'presencial') || $job->city !== null => WorkModality::Presencial,
            default => null,
        };
    }

    private function contract(string $title, string $text): ?string
    {
        return match (true) {
            (bool) preg_match('/\b(estagi|estagio|internship|intern)\b/u', $title) => 'estagio',
            (bool) preg_match('/\b(jovem aprendiz|aprendiz)\b/u', $title) => 'aprendiz',
            (bool) preg_match('/\b(temporari[oa]|safrista|extra de (natal|fim de ano))\b/u', $title.' '.$text) => 'temporario',
            (bool) preg_match('/\b(freela|freelancer|autonom[oa])\b/u', $title) => 'freelancer',
            (bool) preg_match('/\bpj\b|pessoa juridica/u', $text) && ! str_contains($text, 'clt') => 'pj',
            (bool) preg_match('/\bclt\b|efetiv/u', $text) => 'clt',
            default => null,
        };
    }

    private function levelFromText(string $text): ?string
    {
        return match (true) {
            str_contains($text, 'jovem aprendiz') => 'aprendiz',
            (bool) preg_match('/\bestagi/u', $text) => 'estagio',
            default => null,
        };
    }

    private function plain(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}

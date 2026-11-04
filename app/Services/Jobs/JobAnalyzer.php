<?php

namespace App\Services\Jobs;

use App\Contracts\AiClient;
use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Services\SkillCatalog;
use App\Support\Areas;
use App\Support\Grounding;
use App\Support\ProfileNormalizer;
use Illuminate\Support\Str;

/**
 * Pede para a IA ler uma vaga e completar o que a classificação rápida não descobriu:
 * área, nível, escolaridade mínima e habilidades (obrigatórias e diferenciais).
 * Tudo que a IA devolve passa pelo ProfileNormalizer e pela checagem de que está no texto.
 */
class JobAnalyzer
{
    private const MAX_CHARS = 4000;

    public function __construct(private AiClient $ai, private SkillCatalog $catalog)
    {
    }

    /** @return array{fields: array<string, mixed>, required: list<string>, desirable: list<string>} */
    public function analyze(JobPosting $job): array
    {
        $text = $job->title."\n".($job->company ? "Empresa: {$job->company}\n" : '').Str::limit($job->description, self::MAX_CHARS, "\n[...]");
        $raw = $this->ai->generateJson($this->prompt($text), self::schema(), 'parse_job');

        return self::normalize($raw, $text);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{fields: array<string, mixed>, required: list<string>, desirable: list<string>}
     */
    public static function normalize(array $raw, string $sourceText): array
    {
        $plain = Grounding::plain($sourceText);
        $skills = fn ($list) => array_values(array_filter(
            ProfileNormalizer::skills($list),
            fn ($s) => Grounding::isGrounded($s, $plain, 0.34) || Grounding::isGrounded(app(SkillCatalog::class)->canonical($s), $plain, 0.34),
        ));

        $area = ProfileNormalizer::area($raw['area'] ?? null);

        return [
            'fields' => array_filter([
                'area' => $area,
                'level' => ProfileNormalizer::level($raw['nivel'] ?? null)?->value,
                'education_level' => ProfileNormalizer::educationLevel($raw['escolaridade_minima'] ?? null)?->value,
                'ai_summary' => self::summary($raw['resumo'] ?? null, $plain),
            ], fn ($v) => $v !== null),
            'required' => array_slice($skills($raw['habilidades_obrigatorias'] ?? []), 0, 15),
            'desirable' => array_slice($skills($raw['habilidades_desejaveis'] ?? []), 0, 10),
        ];
    }

    private static function summary(mixed $value, string $plainSource): ?string
    {
        $text = is_string($value) ? trim(preg_replace('/\s+/u', ' ', $value)) : '';

        return $text !== '' && Grounding::isGrounded($text, $plainSource, 0.4) ? Str::limit($text, 400, '…') : null;
    }

    private function prompt(string $text): string
    {
        $areas = implode(', ', Areas::keys());
        $levels = implode(', ', array_column(JobLevel::cases(), 'value'));
        $education = implode(', ', array_column(EducationLevel::cases(), 'value'));

        return <<<PROMPT
        Você é um recrutador experiente no Brasil. Leia a vaga abaixo e responda SOMENTE com JSON.
        Use só o que está escrito na vaga. Não invente. Se não houver a informação, use "" ou [].

        - area: uma destas chaves: {$areas}.
        - nivel: uma destas chaves: {$levels}.
        - escolaridade_minima: uma destas chaves, ou "" se a vaga não pedir: {$education}.
        - habilidades_obrigatorias: requisitos que a vaga exige, como aparecem no texto (curtos).
        - habilidades_desejaveis: o que a vaga cita como diferencial ou desejável.
        - resumo: 1 frase sobre o que a pessoa vai fazer nesta vaga, com palavras da vaga.

        VAGA:
        {$text}
        PROMPT;
    }

    /** @return array<string, mixed> */
    public static function schema(): array
    {
        $string = ['type' => 'string'];
        $strings = ['type' => 'array', 'items' => $string];

        return [
            'type' => 'object',
            'properties' => [
                'area' => $string,
                'nivel' => $string,
                'escolaridade_minima' => $string,
                'habilidades_obrigatorias' => $strings,
                'habilidades_desejaveis' => $strings,
                'resumo' => $string,
            ],
            'required' => ['area', 'nivel', 'escolaridade_minima', 'habilidades_obrigatorias', 'habilidades_desejaveis', 'resumo'],
        ];
    }
}

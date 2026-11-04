<?php

namespace App\Services\Resume;

use App\Contracts\AiClient;
use App\Models\User;
use App\Support\ProfileNormalizer;
use Illuminate\Support\Str;

/**
 * Pede para a IA ler o currículo e devolve o perfil já normalizado (ver ProfileNormalizer).
 */
class ResumeAnalyzer
{
    public function __construct(private AiClient $ai)
    {
    }

    /**
     * @return array<string, mixed> resultado de ProfileNormalizer::normalize()
     */
    public function analyze(string $resumeText, ?User $user = null): array
    {
        $text = Str::limit($resumeText, config('faro.resumes.max_chars_for_ai'), "\n[...]");

        $raw = $this->ai->generateJson($this->prompt($text), self::schema(), 'parse_resume', $user);

        return ProfileNormalizer::normalize($raw);
    }

    private function prompt(string $text): string
    {
        return <<<PROMPT
        Você lê currículos de estudantes e profissionais de tecnologia no Brasil.
        Extraia os dados do currículo abaixo e responda SOMENTE com JSON.

        Regras:
        - nivel_desejado: "estagio" se a pessoa é estudante ou busca estágio; senão "junior", "pleno" ou "senior".
        - semestre_atual: número do semestre que a pessoa está cursando, ou 0 se não houver.
        - ano_conclusao: ano previsto de formatura, ou 0 se não houver.
        - habilidades: só tecnologias, ferramentas e competências citadas no currículo. Não invente.
        - modalidades_desejadas: use apenas "remoto", "hibrido" ou "presencial"; lista vazia se não citar.
        - ingles: "nenhum", "basico", "intermediario", "avancado" ou "fluente".
        - cidade: só o nome da cidade; estado: sigla com 2 letras.
        - titulo: até 12 palavras, como "Estudante de Engenharia de Software focado em back-end".
        - resumo: 1 ou 2 frases sobre o perfil profissional, em português.
        - Se uma informação não existir no currículo, use "" (texto vazio).

        CURRÍCULO:
        {$text}
        PROMPT;
    }

    /** @return array<string, mixed> */
    public static function schema(): array
    {
        $string = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'titulo' => $string,
                'resumo' => $string,
                'nivel_desejado' => ['type' => 'string', 'enum' => ['estagio', 'junior', 'pleno', 'senior', '']],
                'area' => $string,
                'cidade' => $string,
                'estado' => $string,
                'semestre_atual' => ['type' => 'integer'],
                'ano_conclusao' => ['type' => 'integer'],
                'ingles' => ['type' => 'string', 'enum' => ['nenhum', 'basico', 'intermediario', 'avancado', 'fluente', '']],
                'modalidades_desejadas' => ['type' => 'array', 'items' => $string],
                'habilidades' => ['type' => 'array', 'items' => $string],
            ],
            'required' => [
                'titulo', 'resumo', 'nivel_desejado', 'area', 'cidade', 'estado',
                'semestre_atual', 'ano_conclusao', 'ingles', 'modalidades_desejadas', 'habilidades',
            ],
        ];
    }
}

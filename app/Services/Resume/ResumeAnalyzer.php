<?php

namespace App\Services\Resume;

use App\Contracts\AiClient;
use App\Enums\ContractType;
use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\User;
use App\Support\Areas;
use App\Services\SkillCatalog;
use App\Support\ProfileNormalizer;
use Illuminate\Support\Str;

/**
 * Pede para a IA ler o currículo (de qualquer área) e devolve o perfil já normalizado.
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

        return self::groundInResume(ProfileNormalizer::normalize($raw), $resumeText);
    }

    /**
     * Modelos pequenos às vezes copiam exemplos do prompt em vez de ler o currículo
     * (aconteceu com o título: "Assistente administrativo com experiência em faturamento"
     * num currículo de Engenharia de Software). Aqui o texto livre só fica se for
     * sustentado pelo próprio currículo; senão o título é montado a partir do cargo desejado.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function groundInResume(array $data, string $resumeText): array
    {
        $resume = ' '.self::plain($resumeText).' ';

        if (filled($data['desired_role'] ?? null) && ! self::isGrounded($data['desired_role'], $resume)) {
            $data['desired_role'] = null;
        }

        if (filled($data['headline'] ?? null) && ! self::isGrounded($data['headline'], $resume)) {
            $data['headline'] = $data['desired_role'] ?? null;
        }

        $data['skills'] = array_values(array_filter(
            $data['skills'] ?? [],
            fn (string $skill) => self::isGrounded($skill, $resume, 0.34)
                || self::isGrounded(app(SkillCatalog::class)->canonical($skill), $resume, 0.34), // "js" → JavaScript
        ));

        return $data;
    }

    /** Pelo menos $ratio das palavras relevantes (3+ letras, sem "de", "com"...) aparecem no currículo. */
    private static function isGrounded(string $phrase, string $plainResume, float $ratio = 0.5): bool
    {
        $stop = ['com', 'para', 'por', 'dos', 'das', 'nos', 'nas', 'uma', 'que', 'and', 'the', 'with', 'em', 'de', 'do', 'da', 'e', 'ou'];
        $words = array_filter(
            preg_split('/[^a-z0-9#+]+/', self::plain($phrase)) ?: [],
            fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, $stop, true),
        );

        if ($words === []) {
            return true;
        }

        // Prefixo de 5 letras tolera plural e flexão ("vendas" x "vendedor" não, mas "faturamento" x "faturar" sim)
        $found = array_filter($words, fn ($w) => str_contains($plainResume, mb_strlen($w) > 5 ? mb_substr($w, 0, 5) : $w));

        return count($found) / count($words) >= $ratio;
    }

    private static function plain(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    private function prompt(string $text): string
    {
        $areas = implode(', ', Areas::keys());
        $levels = implode(', ', array_column(JobLevel::cases(), 'value'));
        $education = implode(', ', array_column(EducationLevel::cases(), 'value'));
        $contracts = implode(', ', array_column(ContractType::cases(), 'value'));

        return <<<PROMPT
        Você é um recrutador experiente no Brasil e lê currículos de qualquer área
        (administração, saúde, vendas, logística, indústria, educação, tecnologia etc.).
        Extraia os dados do currículo abaixo e responda SOMENTE com JSON.

        Regras:
        - Use só o que está escrito no currículo. Não invente. Se não houver a informação, use "" ou [].
        - cargo_desejado: UM cargo curto (até 6 palavras) que a pessoa busca (objetivo) ou, se não houver, o último cargo.
        - area: uma destas chaves: {$areas}.
        - nivel_desejado: uma destas chaves: {$levels}. Estudante buscando estágio = "estagio".
        - escolaridade: maior nível de formação, uma destas chaves: {$education}.
        - contratos: tipos que a pessoa aceita, entre: {$contracts}. Lista vazia se não citar.
        - modalidades_desejadas: apenas "remoto", "hibrido" ou "presencial".
        - semestre_atual e ano_conclusao: números, ou 0 se não houver.
        - idiomas: lista de {"idioma", "nivel"} com nivel em basico, intermediario, avancado, fluente ou nativo.
        - experiencias: empregos, estágios e trabalhos voluntários, do mais recente ao mais antigo.
          inicio e fim no formato "AAAA-MM"; atual = true se a pessoa ainda trabalha lá.
        - formacoes: cursos (superior, técnico, ensino médio, pós), com status cursando, concluido ou trancado.
          inicio e fim são anos (número) ou 0.
        - habilidades: só competências e ferramentas que aparecem escritas no currículo.
        - cnh: categorias da carteira de motorista, como ["B"]. Lista vazia se não citar.
        - cidade: só o nome da cidade; estado: sigla com 2 letras.
        - titulo: até 12 palavras resumindo QUEM É ESTA PESSOA, com o cargo ou a formação dela
          e o principal ponto forte, usando palavras do próprio currículo.
        - resumo: 1 ou 2 frases sobre o perfil profissional desta pessoa, em português.

        CURRÍCULO:
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
                'titulo' => $string,
                'resumo' => $string,
                'cargo_desejado' => $string,
                'area' => $string,
                'nivel_desejado' => $string,
                'escolaridade' => $string,
                'contratos' => $strings,
                'modalidades_desejadas' => $strings,
                'cidade' => $string,
                'estado' => $string,
                'telefone' => $string,
                'linkedin' => $string,
                'semestre_atual' => ['type' => 'integer'],
                'ano_conclusao' => ['type' => 'integer'],
                'idiomas' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['idioma' => $string, 'nivel' => $string],
                    'required' => ['idioma', 'nivel'],
                ]],
                'experiencias' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => [
                        'cargo' => $string, 'empresa' => $string, 'cidade' => $string,
                        'inicio' => $string, 'fim' => $string, 'atual' => ['type' => 'boolean'], 'descricao' => $string,
                    ],
                    'required' => ['cargo', 'empresa', 'inicio', 'fim', 'atual'],
                ]],
                'formacoes' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => [
                        'curso' => $string, 'instituicao' => $string, 'nivel' => $string, 'status' => $string,
                        'inicio' => ['type' => 'integer'], 'fim' => ['type' => 'integer'],
                    ],
                    'required' => ['curso', 'instituicao', 'status'],
                ]],
                'habilidades' => $strings,
                'cnh' => $strings,
            ],
            'required' => [
                'titulo', 'resumo', 'cargo_desejado', 'area', 'nivel_desejado', 'escolaridade', 'contratos',
                'modalidades_desejadas', 'cidade', 'estado', 'semestre_atual', 'ano_conclusao', 'idiomas',
                'experiencias', 'formacoes', 'habilidades', 'cnh',
            ],
        ];
    }
}

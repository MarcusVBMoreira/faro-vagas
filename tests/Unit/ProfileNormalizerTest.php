<?php

namespace Tests\Unit;

use App\Enums\JobLevel;
use App\Support\ProfileNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Casos tirados dos testes reais dos modelos no VPS (ops/vps/01 e 02).
 */
class ProfileNormalizerTest extends TestCase
{
    public function test_fixes_typical_small_model_mistakes(): void
    {
        $data = ProfileNormalizer::normalize([
            'titulo' => '  Estudante de Engenharia de Software ',
            'resumo' => 'Busca estágio em desenvolvimento web.',
            'nivel_desejado' => 'Estágio',
            'area' => 'Desenvolvimento Web',
            'cidade' => 'Mauá/SP',
            'estado' => '',
            'semestre_atual' => '3º semestre',
            'ano_conclusao' => 2029,
            'ingles' => 'intermediário',
            'modalidades_desejadas' => ['estagio', 'híbrido', 'Remoto', 'remoto'],
            'habilidades' => ['PHP', 'php', ' MySQL ', '', 'Git.', str_repeat('x', 80)],
        ]);

        $this->assertSame('Estudante de Engenharia de Software', $data['headline']);
        $this->assertSame(JobLevel::Estagio, $data['desired_level']);
        $this->assertSame('Mauá', $data['city']);
        $this->assertSame('SP', $data['state']);
        $this->assertSame(3, $data['current_semester']);
        $this->assertSame(2029, $data['graduation_year']);
        $this->assertSame('intermediario', $data['english_level']);
        $this->assertSame(['hibrido', 'remoto'], $data['desired_modalities']);
        $this->assertSame(['PHP', 'MySQL', 'Git'], $data['skills']);
    }

    public function test_city_with_dash_and_state(): void
    {
        $this->assertSame(['Santo André', 'SP'], ProfileNormalizer::cityAndState('Santo André - SP', null));
        $this->assertSame(['São Paulo', 'SP'], ProfileNormalizer::cityAndState('São Paulo, SP', 'XX'));
        $this->assertSame(['Campinas', 'SP'], ProfileNormalizer::cityAndState('Campinas', 'sp'));
        $this->assertSame([null, null], ProfileNormalizer::cityAndState('', ''));
    }

    public function test_empty_or_invalid_values_become_null(): void
    {
        $data = ProfileNormalizer::normalize([
            'nivel_desejado' => '',
            'semestre_atual' => 0,
            'ano_conclusao' => 0,
            'ingles' => 'klingon',
            'habilidades' => 'não é lista',
        ]);

        $this->assertNull($data['desired_level']);
        $this->assertNull($data['current_semester']);
        $this->assertNull($data['graduation_year']);
        $this->assertNull($data['english_level']);
        $this->assertSame(['não é lista'], $data['skills']);
        $this->assertSame([], $data['desired_modalities']);
    }

    public function test_levels(): void
    {
        $this->assertSame(JobLevel::Junior, ProfileNormalizer::level('Júnior'));
        $this->assertSame(JobLevel::Senior, ProfileNormalizer::level('sênior'));
        $this->assertSame(JobLevel::Estagio, ProfileNormalizer::level('internship'));
    }
}

<?php

namespace Tests\Unit;

use App\Enums\EducationLevel;
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
        $this->assertSame('tecnologia', $data['area']);
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

    public function test_areas_from_any_profession(): void
    {
        $this->assertSame('saude', ProfileNormalizer::area('Enfermagem'));
        $this->assertSame('saude', ProfileNormalizer::area('saude'));
        $this->assertSame('logistica', ProfileNormalizer::area('Logística, estoque e transporte'));
        $this->assertSame('comercial', ProfileNormalizer::area('Vendas externas'));
        $this->assertSame('rh', ProfileNormalizer::area('RH'));
        $this->assertSame('gastronomia', ProfileNormalizer::area('Cozinheira'));
        $this->assertSame('administracao', ProfileNormalizer::area('Administrativo'));
        $this->assertSame('outros', ProfileNormalizer::area('Astronauta'));
        $this->assertNull(ProfileNormalizer::area(''));
        // palavras curtas não casam dentro de outras ("ong" em "longo", "arte" em "departamento")
        $this->assertNotSame('servico_social', ProfileNormalizer::area('Trabalho de longo prazo'));
    }

    public function test_levels_for_every_career_stage(): void
    {
        $this->assertSame(JobLevel::Aprendiz, ProfileNormalizer::level('Jovem Aprendiz'));
        $this->assertSame(JobLevel::Operacional, ProfileNormalizer::level('Auxiliar'));
        $this->assertSame(JobLevel::Assistente, ProfileNormalizer::level('assistente'));
        $this->assertSame(JobLevel::Gerencia, ProfileNormalizer::level('Gerente'));
        $this->assertSame(JobLevel::Coordenacao, ProfileNormalizer::level('coordenador'));
    }

    public function test_education_levels(): void
    {
        $this->assertSame(EducationLevel::Medio, ProfileNormalizer::educationLevel('Ensino médio completo'));
        $this->assertSame(EducationLevel::MedioIncompleto, ProfileNormalizer::educationLevel('médio incompleto'));
        $this->assertSame(EducationLevel::SuperiorCursando, ProfileNormalizer::educationLevel('Graduação em andamento'));
        $this->assertSame(EducationLevel::Superior, ProfileNormalizer::educationLevel('Bacharelado'));
        $this->assertSame(EducationLevel::PosGraduacao, ProfileNormalizer::educationLevel('MBA'));
        $this->assertSame(EducationLevel::Tecnico, ProfileNormalizer::educationLevel('tecnico'));
        $this->assertNull(ProfileNormalizer::educationLevel('qualquer coisa'));
    }

    public function test_contracts_languages_and_licenses(): void
    {
        $this->assertSame(['clt', 'pj', 'freelancer'], ProfileNormalizer::contracts(['CLT', 'PJ', 'autônomo', 'CLT', 'xyz']));
        $this->assertSame(
            [['language' => 'ingles', 'level' => 'fluente'], ['language' => 'libras', 'level' => null]],
            ProfileNormalizer::languages([['idioma' => 'English', 'nivel' => 'Fluente'], 'Libras', ['idioma' => 'inglês', 'nivel' => 'básico']]),
        );
        $this->assertSame(['A', 'B', 'D'], ProfileNormalizer::driverLicense(['Categoria AB', 'D']));
    }

    public function test_contact_fields(): void
    {
        $this->assertSame('(11) 91234-5678', ProfileNormalizer::phone('+55 (11) 91234-5678'));
        $this->assertSame('(11) 4321-1234', ProfileNormalizer::phone('11 4321 1234'));
        $this->assertNull(ProfileNormalizer::phone('123'));
        $this->assertSame('https://www.linkedin.com/in/ana', ProfileNormalizer::linkedin('linkedin.com/in/ana'));
        $this->assertNull(ProfileNormalizer::linkedin('https://example.com'));
    }

    public function test_month_dates(): void
    {
        $this->assertSame('2023-03-01', ProfileNormalizer::monthDate('2023-03'));
        $this->assertSame('2019-11-01', ProfileNormalizer::monthDate('11/2019'));
        $this->assertSame('2020-01-01', ProfileNormalizer::monthDate('desde 2020'));
        $this->assertNull(ProfileNormalizer::monthDate('13/2020'));
        $this->assertNull(ProfileNormalizer::monthDate('atual'));
    }
}

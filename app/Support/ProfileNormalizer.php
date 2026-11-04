<?php

namespace App\Support;

use App\Enums\ContractType;
use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Enums\WorkModality;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Corrige e padroniza o JSON que a IA devolve ao ler um currículo (de qualquer área).
 *
 * Os testes no VPS mostraram deslizes típicos de modelos pequenos:
 * "3º semestre" em vez de 3, "intermediário" com acento, "Mauá/SP" na cidade,
 * "estagio" misturado nas modalidades. Tudo isso é resolvido aqui, em código.
 */
final class ProfileNormalizer
{
    private const MAX_SKILLS = 40;

    private const MAX_ITEMS = 15;

    /** Palavras-chave → área (App\Support\Areas), na ordem em que são testadas. */
    private const AREA_KEYWORDS = [
        'tecnologia' => ['tecnologia', ' ti ', 'informatica', 'software', 'desenvolv', 'programa', 'dados', 'suporte tecnico', 'redes'],
        'saude' => ['saude', 'enferm', 'medic', 'hospital', 'farmac', 'odonto', 'fisioter', 'nutri', 'clinica'],
        'educacao' => ['educa', 'ensino', 'professor', 'pedagog', 'escola', 'docente'],
        'comercial' => ['comercial', 'venda', 'vendedor', 'representante'],
        'atendimento' => ['atendimento', 'telemarketing', ' sac ', 'call center', 'recepcion'],
        'logistica' => ['logistic', 'estoque', 'almoxarif', 'transporte', 'motorista', 'expedi'],
        'contabilidade' => ['contab', 'financ', 'fiscal', 'banco', 'bancari', 'tesouraria', 'auditoria'],
        'rh' => ['recursos humanos', ' rh ', 'recrutamento', 'departamento pessoal'],
        'direito' => ['direito', 'juridic', 'advoga'],
        'comunicacao' => ['marketing', 'comunica', 'publicidade', 'jornalis', 'social media', 'redes sociais'],
        'design' => ['design', 'criacao', ' ux ', ' ui '],
        'engenharia' => ['engenharia', 'engenheiro'],
        'construcao' => ['construcao', 'obra', 'arquitet', 'pedreiro', 'eletricista predial'],
        'industria' => ['industri', 'producao', 'fabrica', 'operador de maquina', 'montagem'],
        'manutencao' => ['manutencao', 'mecanic', 'eletricista', 'tecnico de manutencao'],
        'gastronomia' => ['gastronom', 'cozinh', 'alimenta', 'restaurante', 'chef', 'garcom', 'confeit'],
        'hotelaria' => ['hotel', 'turismo', 'eventos'],
        'varejo' => ['varejo', 'loja', 'caixa', 'repositor', 'supermercado'],
        'beleza' => ['beleza', 'estetic', 'cabelei', 'manicure', 'barbear'],
        'seguranca' => ['seguranca', 'vigilan', 'portaria', 'porteiro'],
        'limpeza' => ['limpeza', 'servicos gerais', 'zelador', 'auxiliar de limpeza'],
        'agro' => ['agro', 'agricul', 'pecuar', 'rural', 'veterinar'],
        'meio_ambiente' => ['meio ambiente', 'ambiental', 'sustentab'],
        'servico_social' => ['servico social', 'assistente social', 'terceiro setor', ' ong '],
        'arte_cultura' => [' arte', 'cultura', 'musica', 'teatro', 'fotografia', 'audiovisual'],
        'esporte' => ['esporte', 'educacao fisica', 'personal', 'academia'],
        'administracao' => ['administra', 'escritorio', 'secretari', 'assistente administrativo', 'compras'],
    ];

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function normalize(array $raw): array
    {
        [$city, $state] = self::cityAndState(
            self::text($raw['cidade'] ?? null, 80),
            self::text($raw['estado'] ?? null, 40),
        );

        $languages = self::languages($raw['idiomas'] ?? []);
        $english = collect($languages)->firstWhere('language', 'ingles')['level'] ?? self::languageLevel($raw['ingles'] ?? null);

        return [
            'headline' => self::text($raw['titulo'] ?? null, 160),
            'summary' => self::text($raw['resumo'] ?? null, 1000),
            'desired_role' => self::role($raw['cargo_desejado'] ?? null),
            'area' => self::area($raw['area'] ?? null),
            'desired_level' => self::level($raw['nivel_desejado'] ?? null),
            'desired_contracts' => self::contracts($raw['contratos'] ?? []),
            'desired_modalities' => self::modalities($raw['modalidades_desejadas'] ?? []),
            'city' => $city,
            'state' => $state,
            'phone' => self::phone($raw['telefone'] ?? null),
            'linkedin_url' => self::linkedin($raw['linkedin'] ?? null),
            'education_level' => self::educationLevel($raw['escolaridade'] ?? null),
            'current_semester' => self::intBetween($raw['semestre_atual'] ?? null, 1, 12),
            'graduation_year' => self::intBetween($raw['ano_conclusao'] ?? null, 1960, 2045),
            'languages' => $languages,
            'english_level' => $english,
            'driver_license' => self::driverLicense($raw['cnh'] ?? []),
            'skills' => self::skills($raw['habilidades'] ?? []),
            'experiences' => self::experiences($raw['experiencias'] ?? []),
            'educations' => self::educations($raw['formacoes'] ?? []),
        ];
    }

    /** "Estágio em Desenvolvimento de Software, Desenvolvimento Web ou TI" → "Estágio em Desenvolvimento de Software". */
    public static function role(mixed $value): ?string
    {
        $text = self::text($value, 300);
        if ($text === null) {
            return null;
        }
        $first = trim(preg_split('/\s*(?:,|;|\/|\|| ou | or )\s*/iu', $text)[0] ?? '');

        return self::text($first !== '' ? $first : $text, 120);
    }

    public static function level(mixed $value): ?JobLevel
    {
        $v = self::plain($value);

        return match (true) {
            $v === '' => null,
            str_contains($v, 'aprendiz') => JobLevel::Aprendiz,
            str_contains($v, 'estag') || str_contains($v, 'intern') => JobLevel::Estagio,
            str_contains($v, 'trainee') => JobLevel::Trainee,
            str_contains($v, 'diretor') => JobLevel::Diretoria,
            str_contains($v, 'gerent') || str_contains($v, 'gerencia') || str_contains($v, 'manager') => JobLevel::Gerencia,
            str_contains($v, 'coordena') => JobLevel::Coordenacao,
            str_contains($v, 'supervis') || str_contains($v, 'lider') => JobLevel::Supervisao,
            str_contains($v, 'especialista') => JobLevel::Especialista,
            str_contains($v, 'senior') || $v === 'sr' => JobLevel::Senior,
            str_contains($v, 'pleno') => JobLevel::Pleno,
            str_contains($v, 'junior') || $v === 'jr' => JobLevel::Junior,
            str_contains($v, 'tecnic') => JobLevel::Tecnico,
            str_contains($v, 'assistente') => JobLevel::Assistente,
            str_contains($v, 'operacional') || str_contains($v, 'auxiliar') || str_contains($v, 'operador') => JobLevel::Operacional,
            default => null,
        };
    }

    public static function area(mixed $value): ?string
    {
        $v = self::plain($value);
        if ($v === '') {
            return null;
        }

        $key = str_replace([' ', '-'], '_', $v);
        if (in_array($key, Areas::keys(), true)) {
            return $key;
        }

        foreach (Areas::ALL as $areaKey => $label) {
            if ($v === self::plain($label)) {
                return $areaKey;
            }
        }

        $padded = ' '.$v.' ';
        foreach (self::AREA_KEYWORDS as $areaKey => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($padded, $keyword)) {
                    return $areaKey;
                }
            }
        }

        return 'outros';
    }

    public static function educationLevel(mixed $value): ?EducationLevel
    {
        $v = self::plain($value);
        $incomplete = str_contains($v, 'incomplet') || str_contains($v, 'cursando') || str_contains($v, 'andamento');

        if ($v !== '' && ($case = EducationLevel::tryFrom($v))) {
            return $case;
        }

        return match (true) {
            $v === '' => null,
            str_contains($v, 'doutor') || str_contains($v, 'phd') => EducationLevel::Doutorado,
            str_contains($v, 'mestr') => EducationLevel::Mestrado,
            str_contains($v, 'pos') || str_contains($v, 'mba') || str_contains($v, 'especializa') => EducationLevel::PosGraduacao,
            str_contains($v, 'superior') || str_contains($v, 'gradua') || str_contains($v, 'bacharel')
                || str_contains($v, 'licenciatura') || str_contains($v, 'tecnologo') || str_contains($v, 'faculdade')
                => $incomplete ? EducationLevel::SuperiorCursando : EducationLevel::Superior,
            str_contains($v, 'tecnic') => EducationLevel::Tecnico,
            str_contains($v, 'medio') => $incomplete ? EducationLevel::MedioIncompleto : EducationLevel::Medio,
            str_contains($v, 'fundamental') => $incomplete ? EducationLevel::FundamentalIncompleto : EducationLevel::Fundamental,
            default => null,
        };
    }

    public static function languageLevel(mixed $value): ?string
    {
        $v = self::plain($value);

        return match (true) {
            $v === '' => null,
            str_contains($v, 'nativ') || str_contains($v, 'materna') => 'nativo',
            str_contains($v, 'fluen') => 'fluente',
            str_contains($v, 'avanc') || str_contains($v, 'advanced') => 'avancado',
            str_contains($v, 'intermed') => 'intermediario',
            str_contains($v, 'basic') => 'basico',
            default => null,
        };
    }

    /** @return list<array{language: string, level: ?string}> */
    public static function languages(mixed $values): array
    {
        $aliases = [
            'ingles' => ['ingles', 'english'], 'espanhol' => ['espanhol', 'spanish', 'espanol'],
            'portugues' => ['portugues', 'portuguese'], 'frances' => ['frances', 'french'],
            'alemao' => ['alemao', 'german'], 'italiano' => ['italian'], 'mandarim' => ['mandarim', 'chines', 'chinese'],
            'japones' => ['japones', 'japanese'], 'libras' => ['libras', 'sinais'],
        ];

        $found = [];
        foreach ((array) $values as $item) {
            $name = self::plain(is_array($item) ? ($item['idioma'] ?? $item['language'] ?? '') : $item);
            foreach ($aliases as $key => $words) {
                foreach ($words as $word) {
                    if ($name !== '' && str_contains($name, $word)) {
                        $level = is_array($item) ? self::languageLevel($item['nivel'] ?? $item['level'] ?? null) : null;
                        $found[$key] ??= ['language' => $key, 'level' => $level];
                        continue 3;
                    }
                }
            }
        }

        return array_values($found);
    }

    /** @return list<string> */
    public static function contracts(mixed $values): array
    {
        $found = [];
        foreach ((array) $values as $value) {
            $v = self::plain($value);
            $contract = match (true) {
                str_contains($v, 'clt') || str_contains($v, 'efetiv') => ContractType::Clt,
                str_contains($v, 'pj') || str_contains($v, 'pessoa juridica') => ContractType::Pj,
                str_contains($v, 'aprendiz') => ContractType::Aprendiz,
                str_contains($v, 'estag') => ContractType::Estagio,
                str_contains($v, 'tempor') => ContractType::Temporario,
                str_contains($v, 'freela') || str_contains($v, 'autonom') => ContractType::Freelancer,
                default => null,
            };
            if ($contract) {
                $found[$contract->value] = true;
            }
        }

        return array_keys($found);
    }

    /** @return list<string> */
    public static function modalities(mixed $values): array
    {
        $found = [];

        foreach ((array) $values as $value) {
            $v = self::plain($value);
            $modality = match (true) {
                str_contains($v, 'remot') || str_contains($v, 'home') => WorkModality::Remoto,
                str_contains($v, 'hibrid') => WorkModality::Hibrido,
                str_contains($v, 'presenc') => WorkModality::Presencial,
                default => null,
            };
            if ($modality) {
                $found[$modality->value] = true;
            }
        }

        return array_keys($found);
    }

    /** @return list<string> */
    public static function driverLicense(mixed $values): array
    {
        $letters = [];
        foreach ((array) $values as $value) {
            $v = strtoupper((string) (is_string($value) ? $value : ''));
            $v = preg_replace('/CATEGORIA|CNH|CAT\.?/', '', $v);
            foreach (str_split(preg_replace('/[^A-E]/', '', (string) $v)) as $letter) {
                $letters[$letter] = true;
            }
        }
        $letters = array_keys($letters);
        sort($letters);

        return $letters;
    }

    public static function phone(mixed $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) (is_scalar($value) ? $value : ''));
        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }

        return null;
    }

    public static function linkedin(mixed $value): ?string
    {
        $v = trim((string) (is_string($value) ? $value : ''));
        if (! str_contains(Str::lower($v), 'linkedin.com/')) {
            return null;
        }
        $v = preg_replace('#^(https?://)?(www\.)?#i', '', $v);

        return Str::limit('https://www.'.$v, 250, '');
    }

    /**
     * Aceita "Mauá", "Mauá/SP", "Santo André - SP", "São Paulo, SP".
     *
     * @return array{0: ?string, 1: ?string}
     */
    public static function cityAndState(?string $city, ?string $state): array
    {
        $uf = self::uf($state);

        if ($city && preg_match('/^(.+?)\s*(?:\/|-|,)\s*([A-Za-z]{2})$/u', $city, $m)) {
            $city = trim($m[1]);
            $uf ??= self::uf($m[2]);
        }

        return [$city ?: null, $uf];
    }

    /** @return list<string> */
    public static function skills(mixed $values): array
    {
        $unique = [];

        foreach ((array) $values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $skill = trim(preg_replace('/\s+/u', ' ', $value), " \t.,;:-");
            if ($skill === '' || mb_strlen($skill) > 60) {
                continue;
            }
            $unique[mb_strtolower($skill)] ??= $skill;
            if (count($unique) >= self::MAX_SKILLS) {
                break;
            }
        }

        return array_values($unique);
    }

    /** @return list<array<string, mixed>> */
    public static function experiences(mixed $items): array
    {
        $result = [];
        foreach (array_slice((array) $items, 0, self::MAX_ITEMS) as $item) {
            if (! is_array($item) || ! ($role = self::text($item['cargo'] ?? null, 120))) {
                continue;
            }
            $endRaw = self::plain($item['fim'] ?? '');
            $current = ($item['atual'] ?? false) === true
                || in_array($endRaw, ['atual', 'o momento', 'atualmente', 'presente', 'hoje'], true);

            $result[] = [
                'role' => $role,
                'company' => self::text($item['empresa'] ?? null, 120),
                'location' => self::text($item['cidade'] ?? null, 120),
                'start_date' => self::monthDate($item['inicio'] ?? null),
                'end_date' => $current ? null : self::monthDate($item['fim'] ?? null),
                'is_current' => $current,
                'description' => self::text($item['descricao'] ?? null, 1000),
            ];
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    public static function educations(mixed $items): array
    {
        $result = [];
        foreach (array_slice((array) $items, 0, self::MAX_ITEMS) as $item) {
            if (! is_array($item) || ! ($course = self::text($item['curso'] ?? null, 150))) {
                continue;
            }
            $status = self::plain($item['status'] ?? '');

            $result[] = [
                'course' => $course,
                'institution' => self::text($item['instituicao'] ?? null, 150),
                'level' => self::educationLevel($item['nivel'] ?? null)?->value,
                'status' => match (true) {
                    str_contains($status, 'cursando') || str_contains($status, 'andamento') => 'cursando',
                    str_contains($status, 'conclu') || str_contains($status, 'complet') => 'concluido',
                    str_contains($status, 'tranc') || str_contains($status, 'interromp') => 'trancado',
                    default => null,
                },
                'start_year' => self::intBetween($item['inicio'] ?? null, 1950, 2045),
                'end_year' => self::intBetween($item['fim'] ?? null, 1950, 2045),
            ];
        }

        return $result;
    }

    /** "2023-03", "03/2023", "mar 2023" ou "2023" → "2023-03-01". */
    public static function monthDate(mixed $value): ?string
    {
        $v = trim((string) (is_scalar($value) ? $value : ''));

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})/', $v, $m)) {
            [$year, $month] = [(int) $m[1], (int) $m[2]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{4})/', $v, $m)) {
            [$year, $month] = [(int) $m[2], (int) $m[1]];
        } elseif (preg_match('/\b(19|20)\d{2}\b/', $v, $m)) {
            [$year, $month] = [(int) $m[0], 1];
        } else {
            return null;
        }

        if ($month < 1 || $month > 12 || $year < 1950 || $year > (int) date('Y') + 1) {
            return null;
        }

        return CarbonImmutable::create($year, $month, 1)->format('Y-m-d');
    }

    private static function uf(?string $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, Brazil::STATES, true) ? $v : null;
    }

    private static function intBetween(mixed $value, int $min, int $max): ?int
    {
        if (is_int($value)) {
            $n = $value;
        } elseif (is_string($value) && preg_match('/\d+/', $value, $m)) {
            $n = (int) $m[0];
        } else {
            return null;
        }

        return $n >= $min && $n <= $max ? $n : null;
    }

    private static function text(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $v = trim(preg_replace('/\s+/u', ' ', $value));

        return $v === '' ? null : Str::limit($v, $max, '');
    }

    private static function plain(mixed $value): string
    {
        return is_string($value) ? Str::lower(Str::ascii(trim($value))) : '';
    }
}

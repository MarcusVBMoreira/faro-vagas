<?php

namespace App\Support;

use App\Enums\JobLevel;
use App\Enums\WorkModality;
use Illuminate\Support\Str;

/**
 * Corrige e padroniza o JSON que a IA devolve ao ler um currículo.
 *
 * Os testes no VPS mostraram deslizes típicos de modelos pequenos:
 * "3º semestre" em vez de 3, "intermediário" com acento, "Mauá/SP" na cidade,
 * "estagio" misturado nas modalidades. Tudo isso é resolvido aqui, em código.
 */
final class ProfileNormalizer
{
    private const MAX_SKILLS = 40;

    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     headline: ?string, summary: ?string, desired_level: ?JobLevel, area: ?string,
     *     city: ?string, state: ?string, desired_modalities: list<string>,
     *     current_semester: ?int, graduation_year: ?int, english_level: ?string,
     *     skills: list<string>
     * }
     */
    public static function normalize(array $raw): array
    {
        [$city, $state] = self::cityAndState(
            self::text($raw['cidade'] ?? null, 80),
            self::text($raw['estado'] ?? null, 40),
        );

        return [
            'headline' => self::text($raw['titulo'] ?? null, 160),
            'summary' => self::text($raw['resumo'] ?? null, 1000),
            'desired_level' => self::level($raw['nivel_desejado'] ?? null),
            'area' => self::text($raw['area'] ?? null, 60),
            'city' => $city,
            'state' => $state,
            'desired_modalities' => self::modalities($raw['modalidades_desejadas'] ?? []),
            'current_semester' => self::intBetween($raw['semestre_atual'] ?? null, 1, 12),
            'graduation_year' => self::intBetween($raw['ano_conclusao'] ?? null, 2000, 2045),
            'english_level' => self::english($raw['ingles'] ?? null),
            'skills' => self::skills($raw['habilidades'] ?? []),
        ];
    }

    public static function level(mixed $value): ?JobLevel
    {
        $v = self::plain($value);

        return match (true) {
            $v === '' => null,
            str_contains($v, 'estag') || str_contains($v, 'intern') => JobLevel::Estagio,
            str_contains($v, 'junior') || $v === 'jr' => JobLevel::Junior,
            str_contains($v, 'pleno') => JobLevel::Pleno,
            str_contains($v, 'senior') || $v === 'sr' => JobLevel::Senior,
            default => null,
        };
    }

    public static function english(mixed $value): ?string
    {
        $v = self::plain($value);

        return match (true) {
            $v === '' => null,
            str_contains($v, 'fluen') || str_contains($v, 'nativ') => 'fluente',
            str_contains($v, 'avanc') || str_contains($v, 'advanced') => 'avancado',
            str_contains($v, 'intermed') => 'intermediario',
            str_contains($v, 'basic') => 'basico',
            str_contains($v, 'nenhum') || str_contains($v, 'nao') => 'nenhum',
            default => null,
        };
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

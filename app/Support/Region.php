<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Região atendida pelo Faro na coleta de vagas: Grande São Paulo + vagas remotas.
 * (Decisão do projeto: começar regional deixa o match mais relevante e o banco leve;
 * para abrir para o Brasil todo, basta FARO_JOBS_REGION=brasil.)
 */
final class Region
{
    /** Os 39 municípios da Região Metropolitana de São Paulo. */
    public const GRANDE_SP = [
        'Arujá', 'Barueri', 'Biritiba-Mirim', 'Caieiras', 'Cajamar', 'Carapicuíba', 'Cotia', 'Diadema',
        'Embu das Artes', 'Embu-Guaçu', 'Ferraz de Vasconcelos', 'Francisco Morato', 'Franco da Rocha',
        'Guararema', 'Guarulhos', 'Itapecerica da Serra', 'Itapevi', 'Itaquaquecetuba', 'Jandira', 'Juquitiba',
        'Mairiporã', 'Mauá', 'Mogi das Cruzes', 'Osasco', 'Pirapora do Bom Jesus', 'Poá', 'Ribeirão Pires',
        'Rio Grande da Serra', 'Salesópolis', 'Santa Isabel', 'Santana de Parnaíba', 'Santo André',
        'São Bernardo do Campo', 'São Caetano do Sul', 'São Lourenço da Serra', 'São Paulo', 'Suzano',
        'Taboão da Serra', 'Vargem Grande Paulista',
    ];

    private const REMOTE_WORDS = ['remoto', 'remota', 'home office', 'homeoffice', 'remote', 'teletrabalho', 'anywhere', '100% online'];

    /**
     * "Santo André - SP", "São Paulo, São Paulo, Brasil", "Mauá/SP", "Remoto", "Brasil".
     *
     * @return array{city: ?string, state: ?string, remote: bool}
     */
    public static function parse(?string $location): array
    {
        $text = trim((string) $location);
        $plain = self::plain($text);
        $remote = self::mentionsRemote($plain);

        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*(?:,|\/|\s-\s|\(|\)|\|)\s*/u', $text) ?: [])));
        $city = null;
        $state = null;

        foreach ($parts as $i => $part) {
            $p = self::plain($part);
            $isUf = in_array(strtoupper($part), Brazil::STATES, true) && mb_strlen($part) === 2;
            $isStateName = isset(Brazil::STATE_NAMES[$p]);

            // Nome de estado que também é cidade ("São Paulo", "Rio de Janeiro"): é cidade quando
            // vem primeiro e ainda há outra parte depois ("Rio de Janeiro, RJ").
            if ($isStateName && $city === null && (isset($parts[$i + 1]) || $p === 'sao paulo')) {
                $city = self::canonicalCity($part);
                if (! isset($parts[$i + 1])) {
                    $state = Brazil::STATE_NAMES[$p];
                }

                continue;
            }

            if ($state === null && ($isUf || $isStateName)) {
                $state = $isUf ? strtoupper($part) : Brazil::STATE_NAMES[$p];

                continue;
            }

            if ($city === null && ! $isUf && ! $isStateName && ! self::mentionsRemote($p) && ! in_array($p, ['brasil', 'brazil', 'br'], true)) {
                $city = self::canonicalCity($part);
            }
        }

        if ($city !== null && $state === null && self::isGrandeSp($city)) {
            $state = 'SP';
        }

        return ['city' => $city, 'state' => $state, 'remote' => $remote];
    }

    /** A vaga entra na coleta? Remota, ou na Grande SP (ou região desligada com FARO_JOBS_REGION=brasil). */
    public static function accepts(?string $city, ?string $state, bool $remote): bool
    {
        if ($remote || config('faro.jobs.region') === 'brasil') {
            return true;
        }

        return $city !== null && $state !== null && strtoupper($state) === 'SP' && self::isGrandeSp($city);
    }

    public static function isGrandeSp(?string $city): bool
    {
        if ($city === null) {
            return false;
        }
        $wanted = self::plain($city);

        foreach (self::GRANDE_SP as $name) {
            if (self::plain($name) === $wanted) {
                return true;
            }
        }

        return false;
    }

    public static function mentionsRemote(string $plainText): bool
    {
        foreach (self::REMOTE_WORDS as $word) {
            if (str_contains($plainText, $word)) {
                return true;
            }
        }

        return false;
    }

    /** Usa a grafia oficial quando a cidade é da Grande SP ("sao bernardo do campo" → "São Bernardo do Campo"). */
    private static function canonicalCity(string $city): string
    {
        foreach (self::GRANDE_SP as $name) {
            if (self::plain($name) === self::plain($city)) {
                return $name;
            }
        }

        return Str::limit(trim($city), 80, '');
    }

    private static function plain(string $text): string
    {
        return Str::lower(Str::ascii(trim($text)));
    }
}

<?php

namespace App\Support;

final class Brazil
{
    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /** Níveis de idioma (chave gravada no banco => rótulo). */
    public const LANGUAGE_LEVELS = [
        'basico' => 'Básico',
        'intermediario' => 'Intermediário',
        'avancado' => 'Avançado',
        'fluente' => 'Fluente',
        'nativo' => 'Nativo',
    ];

    /** Idiomas mais pedidos em vagas no Brasil. */
    public const LANGUAGES = [
        'ingles' => 'Inglês',
        'espanhol' => 'Espanhol',
        'portugues' => 'Português',
        'frances' => 'Francês',
        'alemao' => 'Alemão',
        'italiano' => 'Italiano',
        'mandarim' => 'Mandarim',
        'japones' => 'Japonês',
        'libras' => 'Libras',
        'outro' => 'Outro',
    ];

    /** Categorias da CNH. */
    public const DRIVER_LICENSES = ['A', 'B', 'C', 'D', 'E'];

    public const AVAILABILITY = [
        'imediata' => 'Imediata',
        '15_dias' => 'Em até 15 dias',
        '30_dias' => 'Em até 30 dias',
        'a_combinar' => 'A combinar',
    ];

    public const EDUCATION_STATUS = [
        'cursando' => 'Cursando',
        'concluido' => 'Concluído',
        'trancado' => 'Trancado',
    ];

    /** @param array<string, string> $map */
    public static function translated(array $map): array
    {
        return array_map(fn (string $label) => __($label), $map);
    }
}

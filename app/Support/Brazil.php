<?php

namespace App\Support;

final class Brazil
{
    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    /** Nome do estado (sem acento, minúsculo) → sigla. */
    public const STATE_NAMES = [
        'acre' => 'AC', 'alagoas' => 'AL', 'amapa' => 'AP', 'amazonas' => 'AM', 'bahia' => 'BA', 'ceara' => 'CE',
        'distrito federal' => 'DF', 'espirito santo' => 'ES', 'goias' => 'GO', 'maranhao' => 'MA', 'mato grosso' => 'MT',
        'mato grosso do sul' => 'MS', 'minas gerais' => 'MG', 'para' => 'PA', 'paraiba' => 'PB', 'parana' => 'PR',
        'pernambuco' => 'PE', 'piaui' => 'PI', 'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS', 'rondonia' => 'RO', 'roraima' => 'RR', 'santa catarina' => 'SC',
        'sao paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO',
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

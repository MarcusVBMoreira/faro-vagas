<?php

namespace App\Support;

/**
 * Áreas profissionais, inspiradas nas categorias de Catho, InfoJobs e Gupy.
 * A chave é gravada no banco; o rótulo é traduzido na exibição.
 */
final class Areas
{
    public const ALL = [
        'administracao' => 'Administração e escritório',
        'agro' => 'Agronegócio e agropecuária',
        'arte_cultura' => 'Arte, cultura e entretenimento',
        'atendimento' => 'Atendimento ao cliente e telemarketing',
        'beleza' => 'Beleza e estética',
        'comercial' => 'Comercial e vendas',
        'comunicacao' => 'Comunicação, marketing e publicidade',
        'construcao' => 'Construção civil e arquitetura',
        'contabilidade' => 'Contabilidade, finanças e bancos',
        'design' => 'Design e criação',
        'direito' => 'Direito e jurídico',
        'educacao' => 'Educação e ensino',
        'engenharia' => 'Engenharia',
        'esporte' => 'Esporte e bem-estar',
        'gastronomia' => 'Gastronomia e alimentação',
        'hotelaria' => 'Hotelaria e turismo',
        'industria' => 'Indústria e produção',
        'limpeza' => 'Limpeza e serviços gerais',
        'logistica' => 'Logística, estoque e transporte',
        'manutencao' => 'Manutenção e técnica',
        'meio_ambiente' => 'Meio ambiente e sustentabilidade',
        'rh' => 'Recursos humanos',
        'saude' => 'Saúde e enfermagem',
        'seguranca' => 'Segurança e vigilância',
        'servico_social' => 'Serviço social e terceiro setor',
        'tecnologia' => 'Tecnologia da informação',
        'varejo' => 'Varejo e comércio',
        'outros' => 'Outras áreas',
    ];

    /** @return array<string, string> chave => rótulo traduzido, em ordem alfabética do rótulo */
    public static function options(): array
    {
        $options = array_map(fn (string $label) => __($label), self::ALL);
        $outros = $options['outros'];
        unset($options['outros']);
        asort($options, SORT_LOCALE_STRING);

        return $options + ['outros' => $outros];
    }

    public static function label(?string $key): ?string
    {
        return $key && isset(self::ALL[$key]) ? __(self::ALL[$key]) : null;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::ALL);
    }
}

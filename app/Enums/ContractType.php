<?php

namespace App\Enums;

enum ContractType: string
{
    case Clt = 'clt';
    case Pj = 'pj';
    case Estagio = 'estagio';
    case Aprendiz = 'aprendiz';
    case Temporario = 'temporario';
    case Freelancer = 'freelancer';

    public function label(): string
    {
        return __(match ($this) {
            self::Clt => 'CLT (efetivo)',
            self::Pj => 'PJ',
            self::Estagio => 'Estágio',
            self::Aprendiz => 'Jovem aprendiz',
            self::Temporario => 'Temporário',
            self::Freelancer => 'Freelancer / autônomo',
        });
    }
}

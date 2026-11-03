<?php

namespace App\Enums;

enum JobLevel: string
{
    case Estagio = 'estagio';
    case Junior = 'junior';
    case Pleno = 'pleno';
    case Senior = 'senior';

    public function label(): string
    {
        return match ($this) {
            self::Estagio => 'Estágio',
            self::Junior => 'Júnior',
            self::Pleno => 'Pleno',
            self::Senior => 'Sênior',
        };
    }
}

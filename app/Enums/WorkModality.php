<?php

namespace App\Enums;

enum WorkModality: string
{
    case Remoto = 'remoto';
    case Hibrido = 'hibrido';
    case Presencial = 'presencial';

    public function label(): string
    {
        return match ($this) {
            self::Remoto => 'Remoto',
            self::Hibrido => 'Híbrido',
            self::Presencial => 'Presencial',
        };
    }
}

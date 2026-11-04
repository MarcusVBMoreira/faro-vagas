<?php

namespace App\Enums;

/**
 * Escolaridade, em ordem crescente (o índice serve para comparar com o mínimo exigido pela vaga).
 */
enum EducationLevel: string
{
    case FundamentalIncompleto = 'fundamental_incompleto';
    case Fundamental = 'fundamental';
    case MedioIncompleto = 'medio_incompleto';
    case Medio = 'medio';
    case Tecnico = 'tecnico';
    case SuperiorCursando = 'superior_cursando';
    case Superior = 'superior';
    case PosGraduacao = 'pos';
    case Mestrado = 'mestrado';
    case Doutorado = 'doutorado';

    public function label(): string
    {
        return __(match ($this) {
            self::FundamentalIncompleto => 'Ensino fundamental incompleto',
            self::Fundamental => 'Ensino fundamental completo',
            self::MedioIncompleto => 'Ensino médio incompleto',
            self::Medio => 'Ensino médio completo',
            self::Tecnico => 'Curso técnico',
            self::SuperiorCursando => 'Ensino superior cursando',
            self::Superior => 'Ensino superior completo',
            self::PosGraduacao => 'Pós-graduação / MBA',
            self::Mestrado => 'Mestrado',
            self::Doutorado => 'Doutorado',
        });
    }

    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }
}

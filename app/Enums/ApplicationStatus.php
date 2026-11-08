<?php

namespace App\Enums;

/**
 * Colunas do Kanban de candidaturas, na ordem em que aparecem.
 */
enum ApplicationStatus: string
{
    case Saved = 'saved';
    case Applied = 'applied';
    case Test = 'test';
    case Interview = 'interview';
    case Offer = 'offer';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __(match ($this) {
            self::Saved => 'Salva',
            self::Applied => 'Candidatura enviada',
            self::Test => 'Teste técnico',
            self::Interview => 'Entrevista',
            self::Offer => 'Proposta',
            self::Rejected => 'Não seguiu',
        });
    }
}

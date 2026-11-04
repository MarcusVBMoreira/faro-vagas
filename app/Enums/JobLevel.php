<?php

namespace App\Enums;

/**
 * Níveis profissionais, do primeiro emprego à diretoria (referência: Catho, InfoJobs, Gupy).
 */
enum JobLevel: string
{
    case Aprendiz = 'aprendiz';
    case Estagio = 'estagio';
    case Trainee = 'trainee';
    case Operacional = 'operacional';
    case Assistente = 'assistente';
    case Tecnico = 'tecnico';
    case Junior = 'junior';
    case Pleno = 'pleno';
    case Senior = 'senior';
    case Especialista = 'especialista';
    case Supervisao = 'supervisao';
    case Coordenacao = 'coordenacao';
    case Gerencia = 'gerencia';
    case Diretoria = 'diretoria';

    public function label(): string
    {
        return __(match ($this) {
            self::Aprendiz => 'Jovem aprendiz',
            self::Estagio => 'Estágio',
            self::Trainee => 'Trainee',
            self::Operacional => 'Operacional / Auxiliar',
            self::Assistente => 'Assistente',
            self::Tecnico => 'Técnico',
            self::Junior => 'Júnior',
            self::Pleno => 'Pleno',
            self::Senior => 'Sênior',
            self::Especialista => 'Especialista',
            self::Supervisao => 'Supervisão',
            self::Coordenacao => 'Coordenação',
            self::Gerencia => 'Gerência',
            self::Diretoria => 'Diretoria',
        });
    }
}

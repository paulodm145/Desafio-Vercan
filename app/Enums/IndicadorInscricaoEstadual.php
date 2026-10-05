<?php

namespace App\Enums;

use App\Contracts\TemRotulo;

enum IndicadorInscricaoEstadual: string implements TemRotulo
{
    case Contribuinte = 'contribuinte';
    case ContribuinteIsento = 'contribuinte_isento';
    case NaoContribuinte = 'nao_contribuinte';

    public function label(): string
    {
        return match ($this) {
            self::Contribuinte => 'Contribuinte',
            self::ContribuinteIsento => 'Contribuinte Isento',
            self::NaoContribuinte => 'Não Contribuinte',
        };
    }
}

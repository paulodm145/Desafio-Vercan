<?php

namespace App\Enums;

use App\Contracts\TemRotulo;

enum TipoEmail: string implements TemRotulo
{
    case Pessoal = 'pessoal';
    case Comercial = 'comercial';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Pessoal => 'Pessoal',
            self::Comercial => 'Comercial',
            self::Outro => 'Outro',
        };
    }
}

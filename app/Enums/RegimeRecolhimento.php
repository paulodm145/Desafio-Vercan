<?php

namespace App\Enums;

use App\Contracts\TemRotulo;

enum RegimeRecolhimento: string implements TemRotulo
{
    case RecolherPrestador = 'recolher_prestador';
    case RetidoTomador = 'retido_tomador';

    public function label(): string
    {
        return match ($this) {
            self::RecolherPrestador => 'A recolher pelo prestador',
            self::RetidoTomador => 'Retido pelo tomador',
        };
    }
}

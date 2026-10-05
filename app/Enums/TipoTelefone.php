<?php

namespace App\Enums;

use App\Contracts\TemRotulo;

enum TipoTelefone: string implements TemRotulo
{
    case Residencial = 'residencial';
    case Comercial = 'comercial';
    case Celular = 'celular';

    public function label(): string
    {
        return match ($this) {
            self::Residencial => 'Residencial',
            self::Comercial => 'Comercial',
            self::Celular => 'Celular',
        };
    }
}

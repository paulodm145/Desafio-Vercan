<?php

namespace App\Enums;

use App\Contracts\TemRotulo;

enum TipoPessoa: string implements TemRotulo
{
    case Fisica = 'fisica';
    case Juridica = 'juridica';

    public function label(): string
    {
        return match ($this) {
            self::Fisica => 'Pessoa Física',
            self::Juridica => 'Pessoa Jurídica',
        };
    }
}

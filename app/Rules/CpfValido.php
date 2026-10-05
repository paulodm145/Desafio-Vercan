<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            $fail('O :attribute informado não é um CPF válido.');

            return;
        }

        for ($posicao = 9; $posicao <= 10; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $resto = $soma % 11;
            $digitoCalculado = $resto < 2 ? 0 : 11 - $resto;

            if ($digitoCalculado !== (int) $cpf[$posicao]) {
                $fail('O :attribute informado não é um CPF válido.');

                return;
            }
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CNPJ no formato alfanumérico (Nota Técnica COFIS/RFB): os 12
 * primeiros caracteres podem ser dígitos ou letras (A-Z), os 2 dígitos
 * verificadores finais permanecem numéricos. O cálculo do módulo 11 usa
 * o valor ASCII de cada caractere menos 48 em vez do dígito literal.
 */
class CnpjValido implements ValidationRule
{
    private const PESOS_DV1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const PESOS_DV2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $value));

        // Os 2 últimos caracteres do formato alfanumérico são sempre dígitos
        // (regex abaixo), então um CNPJ com todos os 14 caracteres iguais só é
        // estruturalmente possível quando esse caractere é um dígito — ex.:
        // "00000000000000" fecha o checksum mod-11 (todo termo da soma é 0) e
        // passaria como "válido" sem essa checagem, igual ao equivalente em CpfValido.
        if (! preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj) || preg_match('/^(.)\1{13}$/', $cnpj) === 1) {
            $fail('O :attribute informado não é um CNPJ válido.');

            return;
        }

        $base = substr($cnpj, 0, 12);
        $dv1 = $this->calcularDigito($base, self::PESOS_DV1);
        $dv2 = $this->calcularDigito($base.$dv1, self::PESOS_DV2);

        if ("{$dv1}{$dv2}" !== substr($cnpj, 12, 2)) {
            $fail('O :attribute informado não é um CNPJ válido.');
        }
    }

    /**
     * @param  array<int, int>  $pesos
     */
    private function calcularDigito(string $base, array $pesos): int
    {
        $soma = 0;

        foreach (str_split($base) as $indice => $caractere) {
            $soma += (ord($caractere) - 48) * $pesos[$indice];
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}

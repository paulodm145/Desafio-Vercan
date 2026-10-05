<?php

namespace Tests\Unit\Rules;

use App\Rules\CnpjValido;
use PHPUnit\Framework\TestCase;

class CnpjValidoTest extends TestCase
{
    private function passou(mixed $valor): bool
    {
        $falhou = false;

        (new CnpjValido)->validate('cnpj', $valor, function () use (&$falhou) {
            $falhou = true;
        });

        return ! $falhou;
    }

    public function test_aceita_cnpj_numerico_valido_com_ou_sem_mascara(): void
    {
        $this->assertTrue($this->passou('00.000.000/0001-91'));
        $this->assertTrue($this->passou('00000000000191'));
    }

    public function test_aceita_cnpj_alfanumerico_valido(): void
    {
        // Exemplo oficial da Nota Técnica COFIS/RFB sobre o CNPJ alfanumérico.
        $this->assertTrue($this->passou('12ABC34501DE35'));
    }

    public function test_rejeita_cnpj_com_digito_verificador_incorreto(): void
    {
        $this->assertFalse($this->passou('00.000.000/0001-92'));
    }

    public function test_rejeita_cnpj_com_tamanho_invalido(): void
    {
        $this->assertFalse($this->passou('123'));
    }

    public function test_rejeita_cnpj_com_todos_os_digitos_iguais(): void
    {
        // Caso notável: com todos os caracteres "0", toda parcela do checksum
        // mod-11 é 0 e os dígitos verificadores calculados batem "00" — passaria
        // sem a checagem explícita de documento placeholder, igual ao CPF 000.000.000-00.
        $this->assertFalse($this->passou('00000000000000'));
        $this->assertFalse($this->passou('11.111.111/1111-11'));
    }
}

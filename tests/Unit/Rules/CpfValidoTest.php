<?php

namespace Tests\Unit\Rules;

use App\Rules\CpfValido;
use PHPUnit\Framework\TestCase;

class CpfValidoTest extends TestCase
{
    private function passou(mixed $valor): bool
    {
        $falhou = false;

        (new CpfValido)->validate('cpf', $valor, function () use (&$falhou) {
            $falhou = true;
        });

        return ! $falhou;
    }

    public function test_aceita_cpf_valido_com_ou_sem_mascara(): void
    {
        $this->assertTrue($this->passou('111.444.777-35'));
        $this->assertTrue($this->passou('11144477735'));
    }

    public function test_rejeita_cpf_com_digito_verificador_incorreto(): void
    {
        $this->assertFalse($this->passou('111.444.777-36'));
    }

    public function test_rejeita_cpf_com_todos_os_digitos_iguais(): void
    {
        $this->assertFalse($this->passou('111.111.111-11'));
    }

    public function test_rejeita_cpf_com_tamanho_invalido(): void
    {
        $this->assertFalse($this->passou('123'));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Cidade;
use App\Models\Fornecedor;
use Illuminate\Database\Seeder;

class FornecedorSeeder extends Seeder
{
    private const QUANTIDADE = 1000;

    private const TAMANHO_LOTE = 200;

    /**
     * Carga simples para testar busca/ordenação/paginação do grid. Depende
     * de "php artisan db:seed --class=LocalidadeSeeder" já ter rodado antes
     * (ou do db:seed padrão, que já chama o LocalidadeSeeder); não é
     * idempotente (gera CNPJ/CPF únicos a cada execução), então rode numa
     * base recém-migrada em vez de repetir sobre uma já populada.
     */
    public function run(): void
    {
        if (Cidade::query()->doesntExist()) {
            $this->command?->warn('Nenhuma cidade cadastrada. Rode "php artisan db:seed --class=LocalidadeSeeder" antes.');

            return;
        }

        Fornecedor::factory()
            ->count(self::QUANTIDADE)
            ->make()
            ->map(fn (Fornecedor $fornecedor) => [
                ...$fornecedor->getAttributes(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->chunk(self::TAMANHO_LOTE)
            ->each(fn ($lote) => Fornecedor::query()->insert($lote->all()));
    }
}

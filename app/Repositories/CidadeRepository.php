<?php

namespace App\Repositories;

use App\Models\Cidade;
use Illuminate\Database\Eloquent\Collection;

class CidadeRepository
{
    public function __construct(private readonly Cidade $model) {}

    public function porEstado(int $estadoId): Collection
    {
        return $this->model
            ->where('estado_id', $estadoId)
            ->orderBy('nome')
            ->get();
    }

    public function buscarPorNomeEEstado(string $nome, int $estadoId): ?Cidade
    {
        return $this->model
            ->where('estado_id', $estadoId)
            ->whereRaw('UPPER(nome) = ?', [mb_strtoupper($nome)])
            ->first();
    }

    /**
     * @param  array{estado_id: int, nome: string, codigo_ibge: int}  $dados
     */
    public function atualizarOuCriarPorCodigoIbge(array $dados): Cidade
    {
        return $this->model->updateOrCreate(
            ['codigo_ibge' => $dados['codigo_ibge']],
            $dados,
        );
    }
}

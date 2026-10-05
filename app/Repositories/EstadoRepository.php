<?php

namespace App\Repositories;

use App\Models\Estado;
use Illuminate\Database\Eloquent\Collection;

class EstadoRepository
{
    public function __construct(private readonly Estado $model) {}

    public function todosOrdenadosPorNome(): Collection
    {
        return $this->model->orderBy('nome')->get();
    }

    public function buscarPorSigla(string $sigla): ?Estado
    {
        return $this->model->where('sigla', strtoupper($sigla))->first();
    }

    /**
     * @param  array{nome: string, sigla: string, codigo_ibge: int}  $dados
     */
    public function atualizarOuCriarPorCodigoIbge(array $dados): Estado
    {
        return $this->model->updateOrCreate(
            ['codigo_ibge' => $dados['codigo_ibge']],
            $dados,
        );
    }
}

<?php

namespace App\Http\Controllers\Integracoes;

use App\Http\Controllers\Controller;
use App\Repositories\CidadeRepository;
use App\Repositories\EstadoRepository;
use App\Services\Integracoes\ViaCepService;
use Illuminate\Http\JsonResponse;

class CepController extends Controller
{
    public function __construct(
        private readonly ViaCepService $viaCep,
        private readonly EstadoRepository $estados,
        private readonly CidadeRepository $cidades,
    ) {}

    public function show(string $cep): JsonResponse
    {
        $endereco = $this->viaCep->buscarPorCep($cep);

        if ($endereco === null) {
            return response()->json(['encontrado' => false], 404);
        }

        $estado = $this->estados->buscarPorSigla($endereco['uf']);
        $cidade = $estado !== null
            ? $this->cidades->buscarPorNomeEEstado($endereco['localidade'], $estado->id)
            : null;

        return response()->json([
            'encontrado' => true,
            'logradouro' => $endereco['logradouro'],
            'complemento' => $endereco['complemento'],
            'bairro' => $endereco['bairro'],
            'estado' => $estado !== null ? ['id' => $estado->id, 'sigla' => $estado->sigla] : null,
            'cidade' => $cidade !== null ? ['id' => $cidade->id, 'nome' => $cidade->nome] : null,
        ]);
    }
}

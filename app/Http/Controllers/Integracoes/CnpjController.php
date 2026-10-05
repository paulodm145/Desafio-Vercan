<?php

namespace App\Http\Controllers\Integracoes;

use App\Http\Controllers\Controller;
use App\Services\Integracoes\ReceitaWsService;
use Illuminate\Http\JsonResponse;

class CnpjController extends Controller
{
    public function __construct(private readonly ReceitaWsService $receitaWs) {}

    public function show(string $cnpj): JsonResponse
    {
        $documento = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $cnpj));
        $dados = $this->receitaWs->buscarPorCnpj($documento);

        if ($dados === null) {
            return response()->json(['encontrado' => false], 404);
        }

        // situacao_cnpj só pode ser persistido se tiver vindo de uma consulta
        // real a este endpoint — ver FornecedorService::situacaoCnpjConfiavel().
        // O campo é readonly no form só na UI; sem isso, nada impediria um
        // POST direto setando qualquer texto arbitrário ali.
        session(["situacao_cnpj_verificada.{$documento}" => $dados['situacao_cnpj']]);

        return response()->json(['encontrado' => true, ...$dados]);
    }
}

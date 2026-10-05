<?php

namespace App\Services\Integracoes;

use App\Exceptions\ServicoExternoIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ReceitaWsService
{
    /**
     * @return array{razao_social: string, nome_fantasia: string, situacao_cnpj: string, cep: string}|null
     *
     * @throws ServicoExternoIndisponivelException
     */
    public function buscarPorCnpj(string $cnpj): ?array
    {
        $cnpj = preg_replace('/[^0-9A-Za-z]/', '', $cnpj);

        // A ReceitaWS responde 400 tanto para CNPJ malformado quanto para CNPJ
        // válido mas não encontrado — nunca lança, apenas checa o corpo.
        try {
            $dados = Http::get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}")->json();
        } catch (ConnectionException $e) {
            // Distinto de "CNPJ não encontrado": aqui a API nem respondeu, então
            // o controller não pode devolver 404 (daria a entender que o CNPJ é
            // inválido, quando na verdade não sabemos).
            throw new ServicoExternoIndisponivelException('Falha ao conectar à ReceitaWS.', previous: $e);
        }

        if (! is_array($dados) || ($dados['status'] ?? null) === 'ERROR') {
            return null;
        }

        return [
            'razao_social' => $dados['nome'] ?? '',
            'nome_fantasia' => $dados['fantasia'] ?? '',
            'situacao_cnpj' => $dados['situacao'] ?? '',
            'cep' => preg_replace('/\D/', '', $dados['cep'] ?? ''),
        ];
    }
}

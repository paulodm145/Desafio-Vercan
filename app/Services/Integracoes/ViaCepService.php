<?php

namespace App\Services\Integracoes;

use App\Exceptions\ServicoExternoIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ViaCepService
{
    /**
     * @return array{cep: string, logradouro: string, complemento: string, bairro: string, localidade: string, uf: string}|null
     *
     * @throws ServicoExternoIndisponivelException
     */
    public function buscarPorCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        try {
            $resposta = Http::get("https://viacep.com.br/ws/{$cep}/json/");
        } catch (ConnectionException $e) {
            // Distinto de "CEP não encontrado": aqui a API nem respondeu, então
            // o controller não pode devolver 404 (daria a entender que o CEP é
            // inválido, quando na verdade não sabemos).
            throw new ServicoExternoIndisponivelException('Falha ao conectar ao ViaCEP.', previous: $e);
        }

        if ($resposta->failed()) {
            return null;
        }

        $dados = $resposta->json();

        // O ViaCEP responde 200 com {"erro": true} quando o CEP não existe,
        // em vez de um 404 — precisa ser checado explicitamente.
        if (! is_array($dados) || ($dados['erro'] ?? false)) {
            return null;
        }

        return $dados;
    }
}

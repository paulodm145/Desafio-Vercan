<?php

namespace App\Services\Integracoes;

use App\Exceptions\ServicoExternoIndisponivelException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class BrasilApiService
{
    private const BASE_URL = 'https://brasilapi.com.br/api/ibge';

    /**
     * @return array<int, array{nome: string, sigla: string, codigo_ibge: int}>
     *
     * @throws ServicoExternoIndisponivelException
     */
    public function listarEstados(): array
    {
        $resposta = $this->buscar(self::BASE_URL.'/uf/v1');

        return array_map(
            fn (array $estado) => [
                'nome' => $estado['nome'],
                'sigla' => $estado['sigla'],
                'codigo_ibge' => (int) $estado['id'],
            ],
            $resposta,
        );
    }

    /**
     * @return array<int, array{nome: string, codigo_ibge: int}>
     *
     * @throws ServicoExternoIndisponivelException
     */
    public function listarCidadesPorUf(string $sigla): array
    {
        $resposta = $this->buscar(self::BASE_URL."/municipios/v1/{$sigla}");

        return array_map(
            fn (array $cidade) => [
                'nome' => $cidade['nome'],
                'codigo_ibge' => (int) $cidade['codigo_ibge'],
            ],
            $resposta,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscar(string $url): array
    {
        // Diferente de ViaCepService/ReceitaWsService, não existe "não encontrado"
        // aqui — é sempre uma lista fixa (estados/municípios do IBGE), então
        // qualquer falha (conexão ou HTTP 4xx/5xx via ->throw()) é inequivocamente
        // "a Brasil API está indisponível", nunca "o dado não existe".
        try {
            return Http::get($url)->throw()->json();
        } catch (ConnectionException|RequestException $e) {
            throw new ServicoExternoIndisponivelException('Falha ao conectar à Brasil API.', previous: $e);
        }
    }
}

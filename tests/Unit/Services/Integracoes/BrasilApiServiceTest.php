<?php

namespace Tests\Unit\Services\Integracoes;

use App\Exceptions\ServicoExternoIndisponivelException;
use App\Services\Integracoes\BrasilApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrasilApiServiceTest extends TestCase
{
    public function test_listar_estados_lanca_excecao_padronizada_em_falha_de_conexao(): void
    {
        Http::fake(['brasilapi.com.br/*' => fn () => throw new ConnectionException('timeout')]);

        $this->expectException(ServicoExternoIndisponivelException::class);

        app(BrasilApiService::class)->listarEstados();
    }

    public function test_listar_estados_lanca_excecao_padronizada_em_resposta_de_erro(): void
    {
        Http::fake(['brasilapi.com.br/*' => Http::response(['message' => 'erro'], 500)]);

        $this->expectException(ServicoExternoIndisponivelException::class);

        app(BrasilApiService::class)->listarEstados();
    }
}

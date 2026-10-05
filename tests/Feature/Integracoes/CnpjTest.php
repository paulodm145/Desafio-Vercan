<?php

namespace Tests\Feature\Integracoes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CnpjTest extends TestCase
{
    use RefreshDatabase;

    public function test_cnpj_encontrado_retorna_200(): void
    {
        Http::fake(['receitaws.com.br/*' => Http::response([
            'status' => 'OK',
            'nome' => 'Banco do Brasil SA',
            'fantasia' => 'Direção Geral',
            'situacao' => 'ATIVA',
            'cep' => '70.040-912',
        ])]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('cnpjs.show', '00000000000191'));

        $resposta->assertOk();
        $resposta->assertJson(['encontrado' => true]);
    }

    public function test_cnpj_nao_encontrado_retorna_404(): void
    {
        Http::fake(['receitaws.com.br/*' => Http::response(['status' => 'ERROR'], 400)]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('cnpjs.show', '00000000000000'));

        $resposta->assertStatus(404);
        $resposta->assertJson(['encontrado' => false]);
    }

    public function test_falha_de_conexao_com_receitaws_retorna_503(): void
    {
        Http::fake(['receitaws.com.br/*' => fn () => throw new ConnectionException('timeout')]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('cnpjs.show', '00000000000191'));

        $resposta->assertStatus(503);
    }
}

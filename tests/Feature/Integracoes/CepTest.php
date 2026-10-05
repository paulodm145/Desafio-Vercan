<?php

namespace Tests\Feature\Integracoes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CepTest extends TestCase
{
    use RefreshDatabase;

    public function test_cep_encontrado_retorna_200(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response([
            'cep' => '70040-912',
            'logradouro' => 'SAUN Quadra 5',
            'complemento' => '',
            'bairro' => 'Asa Norte',
            'localidade' => 'Brasília',
            'uf' => 'DF',
        ])]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('ceps.show', '70040912'));

        $resposta->assertOk();
        $resposta->assertJson(['encontrado' => true]);
    }

    public function test_cep_nao_encontrado_retorna_404(): void
    {
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => true])]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('ceps.show', '00000000'));

        $resposta->assertStatus(404);
        $resposta->assertJson(['encontrado' => false]);
    }

    public function test_falha_de_conexao_com_viacep_retorna_503(): void
    {
        Http::fake(['viacep.com.br/*' => fn () => throw new ConnectionException('timeout')]);

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('ceps.show', '70040912'));

        $resposta->assertStatus(503);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FornecedorGridTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_paginada_no_formato_esperado_pelo_tabulator(): void
    {
        Cidade::factory()->count(3)->create();
        Fornecedor::factory()->count(25)->create();

        $resposta = $this->actingAs(User::factory()->create())
            ->getJson(route('fornecedores.dados', ['page' => 1, 'size' => 10]));

        $resposta->assertOk();
        $resposta->assertJsonStructure(['data', 'last_page', 'last_row']);
        $this->assertCount(10, $resposta->json('data'));
        $this->assertSame(3, $resposta->json('last_page'));
        $this->assertSame(25, $resposta->json('last_row'));
    }

    public function test_busca_global_filtra_por_razao_social_nome_fantasia_e_documento(): void
    {
        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Comercial Alfa Ltda']);
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Distribuidora Beta']);

        $resposta = $this->actingAs(User::factory()->create())->getJson(
            route('fornecedores.dados').'?'.http_build_query([
                'filter' => [['field' => 'busca_global', 'type' => 'like', 'value' => 'Alfa']],
            ])
        );

        $resposta->assertOk();
        $this->assertCount(1, $resposta->json('data'));
        $this->assertSame('Comercial Alfa Ltda', $resposta->json('data.0.razao_social_nome'));
    }

    public function test_busca_global_por_ativo_ou_inativo_filtra_pelo_status_da_badge(): void
    {
        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Fornecedor Ligado', 'ativo' => true]);
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Fornecedor Desligado', 'ativo' => false]);

        $usuario = User::factory()->create();

        $respostaAtivo = $this->actingAs($usuario)->getJson(
            route('fornecedores.dados').'?'.http_build_query([
                'filter' => [['field' => 'busca_global', 'type' => 'like', 'value' => 'Ativo']],
            ])
        );

        $respostaAtivo->assertOk();
        $this->assertCount(1, $respostaAtivo->json('data'));
        $this->assertSame('Fornecedor Ligado', $respostaAtivo->json('data.0.razao_social_nome'));

        // "Inativo" contém "ativo" como substring — se a busca usasse `contains`
        // em vez de prefixo, esse filtro voltaria os dois registros, não só o inativo.
        $respostaInativo = $this->actingAs($usuario)->getJson(
            route('fornecedores.dados').'?'.http_build_query([
                'filter' => [['field' => 'busca_global', 'type' => 'like', 'value' => 'Inativo']],
            ])
        );

        $respostaInativo->assertOk();
        $this->assertCount(1, $respostaInativo->json('data'));
        $this->assertSame('Fornecedor Desligado', $respostaInativo->json('data.0.razao_social_nome'));
    }

    public function test_ordenacao_ignora_campo_fora_da_allowlist(): void
    {
        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'cnpj_cpf' => '11111111111']);

        $resposta = $this->actingAs(User::factory()->create())->getJson(
            route('fornecedores.dados').'?'.http_build_query([
                'sort' => [['field' => 'id); drop table fornecedores;--', 'dir' => 'asc']],
            ])
        );

        $resposta->assertOk();
        $this->assertDatabaseHas('fornecedores', ['cnpj_cpf' => '11111111111']);
    }

    public function test_exclui_fornecedor_e_seus_contatos_em_cascata(): void
    {
        $cidade = Cidade::factory()->create();
        $fornecedor = Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id]);
        $contato = $fornecedor->contatos()->create(['principal' => true]);
        $contato->telefones()->create(['numero' => '11999999999', 'tipo' => 'celular']);
        $contato->emails()->create(['email' => 'contato@teste.com', 'tipo' => 'comercial']);

        $resposta = $this->actingAs(User::factory()->create())
            ->deleteJson(route('fornecedores.destroy', $fornecedor));

        $resposta->assertOk();
        $this->assertDatabaseMissing('fornecedores', ['id' => $fornecedor->id]);
        $this->assertDatabaseMissing('fornecedor_contatos', ['id' => $contato->id]);
        $this->assertDatabaseMissing('fornecedor_telefones', ['fornecedor_contato_id' => $contato->id]);
        $this->assertDatabaseMissing('fornecedor_emails', ['fornecedor_contato_id' => $contato->id]);
    }
}

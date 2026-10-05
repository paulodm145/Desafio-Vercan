<?php

namespace Tests\Feature;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Fornecedor;
use App\Models\User;
use App\Services\FornecedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FornecedorTest extends TestCase
{
    use RefreshDatabase;

    private function payloadValido(Cidade $cidade): array
    {
        return [
            'tipo_pessoa' => 'juridica',
            'cnpj_cpf' => '00.000.000/0001-91',
            'razao_social' => 'Banco do Brasil SA',
            'nome_fantasia' => 'Direção Geral',
            'recolhimento' => 'recolher_prestador',
            'ativo' => '1',
            'cep' => '70040-912',
            'logradouro' => 'SAUN Quadra 5',
            'numero' => '100',
            'bairro' => 'Asa Norte',
            'estado_id' => $cidade->estado_id,
            'cidade_id' => $cidade->id,
            'condominio' => '0',
            'contato_principal' => [
                'telefones' => [['numero' => '(61) 99999-9999', 'tipo' => 'celular']],
                'emails' => [['email' => 'contato@bb.com.br', 'tipo' => 'comercial']],
            ],
            'contatos_adicionais' => [
                ['nome' => 'Fulano de Tal', 'telefones' => [['numero' => '(11) 3000-0000', 'tipo' => 'comercial']]],
            ],
        ];
    }

    public function test_cadastra_fornecedor_com_contatos_telefones_e_emails(): void
    {
        $cidade = Cidade::factory()->create();

        $resposta = $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $this->payloadValido($cidade));

        $resposta->assertRedirect(route('fornecedores.index'));

        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $this->assertNotNull($fornecedor);
        $this->assertSame(2, $fornecedor->contatos()->count());

        $principal = $fornecedor->contatos()->where('principal', true)->first();
        $this->assertSame('61999999999', $principal->telefones()->first()->numero);
        $this->assertSame('contato@bb.com.br', $principal->emails()->first()->email);

        $adicional = $fornecedor->contatos()->where('principal', false)->first();
        $this->assertSame('Fulano de Tal', $adicional->nome);
        $this->assertSame('1130000000', $adicional->telefones()->first()->numero);
    }

    public function test_rejeita_cnpj_cpf_duplicado(): void
    {
        $cidade = Cidade::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('fornecedores.store'), $this->payloadValido($cidade));

        $resposta = $this->actingAs($usuario)->post(route('fornecedores.store'), $this->payloadValido($cidade));

        $resposta->assertSessionHasErrors('cnpj_cpf');
        $this->assertSame(1, Fornecedor::query()->where('cnpj_cpf', '00000000000191')->count());
    }

    public function test_atualizacao_nao_rejeita_o_proprio_cnpj_cpf(): void
    {
        $cidade = Cidade::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('fornecedores.store'), $this->payloadValido($cidade));
        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $payload = $this->payloadValido($cidade);
        $payload['razao_social'] = 'Banco do Brasil SA - Atualizado';

        $resposta = $this->actingAs($usuario)->put(route('fornecedores.update', $fornecedor), $payload);

        $resposta->assertRedirect(route('fornecedores.index'));
        $this->assertSame('Banco do Brasil SA - Atualizado', $fornecedor->refresh()->razao_social);
    }

    public function test_atualizacao_remove_contatos_telefones_e_emails_orfaos(): void
    {
        $cidade = Cidade::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('fornecedores.store'), $this->payloadValido($cidade));
        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $contatoPrincipalAntigo = $fornecedor->contatos()->where('principal', true)->first();
        $telefonePrincipalAntigoId = $contatoPrincipalAntigo->telefones()->first()->id;
        $emailPrincipalAntigoId = $contatoPrincipalAntigo->emails()->first()->id;
        $contatoAdicionalAntigo = $fornecedor->contatos()->where('principal', false)->first();

        $payload = $this->payloadValido($cidade);
        $payload['contato_principal'] = [
            'telefones' => [['numero' => '(21) 98888-7777', 'tipo' => 'residencial']],
            'emails' => [['email' => 'novo@bb.com.br', 'tipo' => 'pessoal']],
        ];
        unset($payload['contatos_adicionais']);

        $this->actingAs($usuario)->put(route('fornecedores.update', $fornecedor), $payload);

        $this->assertDatabaseMissing('fornecedor_contatos', ['id' => $contatoAdicionalAntigo->id]);
        $this->assertDatabaseMissing('fornecedor_telefones', ['id' => $telefonePrincipalAntigoId]);
        $this->assertDatabaseMissing('fornecedor_emails', ['id' => $emailPrincipalAntigoId]);

        $novoPrincipal = $fornecedor->contatos()->where('principal', true)->first();
        $this->assertSame('21988887777', $novoPrincipal->telefones()->first()->numero);
        $this->assertSame('novo@bb.com.br', $novoPrincipal->emails()->first()->email);
        $this->assertSame(1, $fornecedor->contatos()->count());
    }

    public function test_exige_inscricao_estadual_quando_indicador_e_contribuinte(): void
    {
        $cidade = Cidade::factory()->create();
        $payload = $this->payloadValido($cidade);
        $payload['indicador_inscricao_estadual'] = 'contribuinte';

        $resposta = $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $payload);

        $resposta->assertSessionHasErrors('inscricao_estadual');
    }

    public function test_nao_exige_inscricao_estadual_quando_indicador_e_nao_contribuinte(): void
    {
        $cidade = Cidade::factory()->create();
        $payload = $this->payloadValido($cidade);
        $payload['indicador_inscricao_estadual'] = 'nao_contribuinte';

        $resposta = $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $payload);

        $resposta->assertSessionDoesntHaveErrors('inscricao_estadual');
    }

    public function test_exige_estado_e_cidade_compativeis(): void
    {
        $outroEstado = Estado::factory()->create();
        $cidadeDeOutroEstado = Cidade::factory()->create();

        $payload = $this->payloadValido($cidadeDeOutroEstado);
        $payload['estado_id'] = $outroEstado->id;

        $resposta = $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $payload);

        $resposta->assertSessionHasErrors('cidade_id');
    }

    public function test_corrida_no_documento_duplicado_vira_erro_de_validacao_nao_500(): void
    {
        $cidade = Cidade::factory()->create();
        $dados = $this->payloadValido($cidade);
        $dados['cnpj_cpf'] = '00000000000191';

        $service = app(FornecedorService::class);
        $service->criar($dados);

        // Simula a corrida: duas requisições concorrentes passariam ambas pelo
        // SELECT do Rule::unique antes de qualquer INSERT acontecer — aqui
        // pulamos direto para o Service, que é onde a colisão real acontece.
        try {
            $service->criar($dados);
            $this->fail('Esperava ValidationException ao colidir com um cnpj_cpf já existente.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cnpj_cpf', $e->errors());
        }

        $this->assertSame(1, Fornecedor::query()->where('cnpj_cpf', '00000000000191')->count());
    }

    public function test_sanitiza_html_malicioso_no_campo_observacoes(): void
    {
        $cidade = Cidade::factory()->create();

        $payload = $this->payloadValido($cidade);
        $payload['observacoes'] = '<p onclick="roubar()">Ok<script>alert(document.cookie)</script></p><img src=x onerror=alert(1)>';

        $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $payload);

        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $this->assertStringNotContainsString('<script', $fornecedor->observacoes);
        $this->assertStringNotContainsString('onclick', $fornecedor->observacoes);
        $this->assertStringNotContainsString('<img', $fornecedor->observacoes);
        $this->assertStringContainsString('<p>Ok</p>', $fornecedor->observacoes);
    }

    public function test_ignora_situacao_cnpj_forjada_sem_consulta_real_a_receitaws(): void
    {
        $cidade = Cidade::factory()->create();
        $payload = $this->payloadValido($cidade);
        $payload['situacao_cnpj'] = 'ATIVA (forjado direto no POST, sem nunca consultar a ReceitaWS)';

        $this->actingAs(User::factory()->create())
            ->post(route('fornecedores.store'), $payload);

        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $this->assertNull($fornecedor->situacao_cnpj);
    }

    public function test_persiste_situacao_cnpj_vinda_de_uma_consulta_real_a_receitaws(): void
    {
        Http::fake([
            'https://www.receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'Banco do Brasil SA',
                'fantasia' => 'Direção Geral',
                'situacao' => 'ATIVA',
                'cep' => '70.040-912',
            ]),
        ]);

        $cidade = Cidade::factory()->create();
        $usuario = User::factory()->create();

        // Mesma sessão do POST seguinte — simula o fluxo real: o JS consulta
        // /cnpjs/{cnpj} no blur do campo antes do usuário clicar em salvar.
        $this->actingAs($usuario)->getJson(route('cnpjs.show', '00000000000191'));

        $payload = $this->payloadValido($cidade);
        $payload['situacao_cnpj'] = 'TEXTO QUALQUER ENVIADO NO POST, DEVE SER IGNORADO';

        $this->actingAs($usuario)->post(route('fornecedores.store'), $payload);

        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();

        $this->assertSame('ATIVA', $fornecedor->situacao_cnpj);
    }

    public function test_atualizacao_sem_nova_consulta_preserva_situacao_cnpj_ja_persistida(): void
    {
        $cidade = Cidade::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('fornecedores.store'), $this->payloadValido($cidade));
        $fornecedor = Fornecedor::query()->where('cnpj_cpf', '00000000000191')->first();
        $fornecedor->forceFill(['situacao_cnpj' => 'ATIVA'])->save();

        $payload = $this->payloadValido($cidade);
        $payload['razao_social'] = 'Banco do Brasil SA - Atualizado';
        $payload['situacao_cnpj'] = 'BAIXADA (tentativa de forjar na edição)';

        $this->actingAs($usuario)->put(route('fornecedores.update', $fornecedor), $payload);

        $this->assertSame('ATIVA', $fornecedor->refresh()->situacao_cnpj);
    }
}

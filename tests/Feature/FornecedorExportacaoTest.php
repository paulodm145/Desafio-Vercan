<?php

namespace Tests\Feature;

use App\Exports\FornecedoresExport;
use App\Models\Cidade;
use App\Models\Fornecedor;
use App\Models\User;
use App\Services\FornecedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FornecedorExportacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exportacao_traz_todos_os_registros_filtrados_sem_limite_de_paginacao(): void
    {
        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->count(25)->create([
            'cidade_id' => $cidade->id,
            'estado_id' => $cidade->estado_id,
            'razao_social' => 'Comercial Alfa Ltda',
        ]);
        Fornecedor::factory()->count(5)->create([
            'cidade_id' => $cidade->id,
            'estado_id' => $cidade->estado_id,
            'razao_social' => 'Distribuidora Beta',
        ]);

        $resultado = app(FornecedorService::class)->listarParaExportacao([
            'filter' => [['field' => 'busca_global', 'type' => 'like', 'value' => 'Alfa']],
        ]);

        $this->assertCount(25, $resultado);
    }

    public function test_exportacao_excel_respeita_filtro_aplicado_na_tela(): void
    {
        Excel::fake();

        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Comercial Alfa Ltda']);
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id, 'razao_social' => 'Distribuidora Beta']);

        $resposta = $this->actingAs(User::factory()->create())->get(
            route('fornecedores.exportar-excel').'?'.http_build_query([
                'filter' => [['field' => 'busca_global', 'type' => 'like', 'value' => 'Alfa']],
            ])
        );

        $resposta->assertOk();
        Excel::assertDownloaded('fornecedores.xlsx', fn (FornecedoresExport $export) => $export->collection()->count() === 1
            && $export->collection()->first()->razao_social === 'Comercial Alfa Ltda');
    }

    public function test_exportacao_pdf_responde_com_cabecalhos_de_download_corretos(): void
    {
        $cidade = Cidade::factory()->create();
        Fornecedor::factory()->create(['cidade_id' => $cidade->id, 'estado_id' => $cidade->estado_id]);

        $resposta = $this->actingAs(User::factory()->create())->get(route('fornecedores.exportar-pdf'));

        $resposta->assertOk();
        $resposta->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('fornecedores.pdf', $resposta->headers->get('content-disposition'));
    }
}

<?php

namespace Database\Seeders;

use App\Repositories\CidadeRepository;
use App\Repositories\EstadoRepository;
use App\Services\Integracoes\BrasilApiService;
use Illuminate\Database\Seeder;

class LocalidadeSeeder extends Seeder
{
    /**
     * Idempotente (updateOrCreate por codigo_ibge), então seguro de rodar
     * de novo em cima de uma base já populada.
     */
    public function run(BrasilApiService $brasilApi, EstadoRepository $estados, CidadeRepository $cidades): void
    {
        $dadosEstados = $brasilApi->listarEstados();
        $barra = $this->command?->getOutput()?->createProgressBar(count($dadosEstados));
        $barra?->start();

        foreach ($dadosEstados as $dadosEstado) {
            $estado = $estados->atualizarOuCriarPorCodigoIbge($dadosEstado);

            foreach ($brasilApi->listarCidadesPorUf($dadosEstado['sigla']) as $dadosCidade) {
                $cidades->atualizarOuCriarPorCodigoIbge([...$dadosCidade, 'estado_id' => $estado->id]);
            }

            $barra?->advance();
        }

        $barra?->finish();
        $this->command?->newLine();
    }
}

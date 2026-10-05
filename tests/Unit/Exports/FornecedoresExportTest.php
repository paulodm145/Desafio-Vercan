<?php

namespace Tests\Unit\Exports;

use App\Exports\FornecedoresExport;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class FornecedoresExportTest extends TestCase
{
    private function celula(): Cell
    {
        return (new Spreadsheet)->getActiveSheet()->getCell('A1');
    }

    public function test_neutraliza_valores_que_comecam_com_prefixo_de_formula(): void
    {
        $export = new FornecedoresExport(new Collection);

        foreach (['=HYPERLINK("http://evil.com","clique")', '+1+1', '-1-1', '@SUM(1,1)'] as $valorPerigoso) {
            $celula = $this->celula();
            $export->bindValue($celula, $valorPerigoso);

            $this->assertSame(DataType::TYPE_STRING, $celula->getDataType());
            $this->assertSame($valorPerigoso, $celula->getValue());
        }
    }

    public function test_preserva_comportamento_padrao_para_valores_normais(): void
    {
        $export = new FornecedoresExport(new Collection);

        $celulaTexto = $this->celula();
        $export->bindValue($celulaTexto, 'Comercial Alfa Ltda');
        $this->assertSame(DataType::TYPE_STRING, $celulaTexto->getDataType());

        $celulaNumero = $this->celula();
        $export->bindValue($celulaNumero, 42);
        $this->assertSame(DataType::TYPE_NUMERIC, $celulaNumero->getDataType());
    }
}

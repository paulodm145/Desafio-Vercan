<?php

namespace App\Exports;

use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * @implements FromCollection<int, Fornecedor>
 * @implements WithMapping<Fornecedor>
 */
class FornecedoresExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping
{
    /**
     * Caracteres que o Excel (e qualquer app que trate o conteúdo como CSV)
     * interpreta como início de fórmula. razao_social/nome_fantasia/nome/apelido
     * são texto livre digitado pelo usuário no formulário de fornecedor — sem
     * isso, um valor como `=HYPERLINK("http://evil.com","clique")` vira uma
     * fórmula executável ao abrir o arquivo exportado (Formula/CSV Injection).
     *
     * @var array<int, string>
     */
    private const PREFIXOS_DE_FORMULA = ['=', '+', '-', '@'];

    /**
     * @param  Collection<int, Fornecedor>  $fornecedores
     */
    public function __construct(private readonly Collection $fornecedores) {}

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && $value !== '' && in_array($value[0], self::PREFIXOS_DE_FORMULA, true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(): Collection
    {
        return $this->fornecedores;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Razão Social / Nome', 'Nome Fantasia / Apelido', 'CNPJ/CPF', 'Ativo'];
    }

    /**
     * @return array<int, string>
     */
    public function map($fornecedor): array
    {
        return [
            $fornecedor->nome_exibicao,
            $fornecedor->nome_fantasia_exibicao,
            $fornecedor->cnpj_cpf_formatado,
            $fornecedor->ativo ? 'Sim' : 'Não',
        ];
    }
}

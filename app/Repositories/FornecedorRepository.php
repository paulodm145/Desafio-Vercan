<?php

namespace App\Repositories;

use App\Models\Fornecedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FornecedorRepository
{
    /**
     * Mapa de campo enviado pelo grid -> expressão SQL segura para ordenar.
     * Mantido como allowlist para nunca interpolar um nome de coluna vindo
     * do cliente diretamente em orderByRaw().
     */
    private const COLUNAS_ORDENAVEIS = [
        'razao_social_nome' => 'coalesce(razao_social, nome)',
        'nome_fantasia_apelido' => 'coalesce(nome_fantasia, apelido)',
        'cnpj_cpf' => 'cnpj_cpf',
        'ativo' => 'ativo',
    ];

    /**
     * Rótulo exibido na grid (ver formatarBadgeAtivo em formatadores.js) para
     * cada valor da coluna booleana `ativo` — usado pela busca global pra
     * reconhecer "ativo"/"inativo" como filtro de status, não texto literal.
     */
    private const ROTULOS_ATIVO = ['ativo' => true, 'inativo' => false];

    public function __construct(private readonly Fornecedor $model) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Fornecedor
    {
        return $this->model->create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Fornecedor $fornecedor, array $dados): Fornecedor
    {
        $fornecedor->update($dados);

        return $fornecedor;
    }

    public function excluir(Fornecedor $fornecedor): void
    {
        $fornecedor->delete();
    }

    public function cnpjCpfCadastrado(string $cnpjCpf, ?int $ignorarId = null): bool
    {
        return $this->model
            ->where('cnpj_cpf', $cnpjCpf)
            ->when($ignorarId !== null, fn ($query) => $query->whereNot('id', $ignorarId))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    public function paginar(array $parametros): LengthAwarePaginator
    {
        $porPagina = max(1, (int) ($parametros['size'] ?? 20));
        $pagina = max(1, (int) ($parametros['page'] ?? 1));

        return $this->consultaFiltrada($parametros)->paginate($porPagina, ['*'], 'page', $pagina);
    }

    /**
     * Mesmo filtro/ordenação do grid, mas sem paginação — para exportações,
     * que devem conter todos os registros que batem com a busca em tela, não
     * só a página visível no momento.
     *
     * @param  array<string, mixed>  $parametros
     */
    public function listarParaExportacao(array $parametros): Collection
    {
        return $this->consultaFiltrada($parametros)->get();
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    private function consultaFiltrada(array $parametros): Builder
    {
        // Builder explícito aqui, não $this->model direto: a variável é
        // passada por vários métodos que vão acumulando condições na mesma
        // query, não é uma chamada encadeada única.
        $consulta = $this->model->newQuery();

        $this->aplicarBusca($consulta, $parametros['filter'] ?? []);
        $comOrdenacao = $this->aplicarOrdenacao($consulta, $parametros['sort'] ?? []);

        if (! $comOrdenacao) {
            $consulta->orderByDesc('id');
        }

        return $consulta;
    }

    /**
     * @param  array<int, array{field?: string, value?: string}>  $filtros
     */
    private function aplicarBusca(Builder $consulta, array $filtros): void
    {
        foreach ($filtros as $filtro) {
            if (($filtro['field'] ?? null) !== 'busca_global') {
                continue;
            }

            $termo = trim((string) ($filtro['value'] ?? ''));

            if ($termo === '') {
                continue;
            }

            // LOWER(coluna) LIKE LOWER(?) em vez de ilike: ilike é exclusivo do
            // Postgres e quebra os testes, que rodam em sqlite.
            $termoNormalizado = mb_strtolower($termo);
            $valor = '%'.$termoNormalizado.'%';
            $colunas = ['razao_social', 'nome', 'nome_fantasia', 'apelido', 'cnpj_cpf'];

            $consulta->where(function (Builder $query) use ($valor, $colunas, $termoNormalizado) {
                foreach ($colunas as $indice => $coluna) {
                    $metodo = $indice === 0 ? 'whereRaw' : 'orWhereRaw';
                    $query->{$metodo}("LOWER({$coluna}) LIKE ?", [$valor]);
                }

                // Prefixo, não `str_contains`: "inativo" contém "ativo" como
                // substring, então um `contains` faria o termo "ativo" bater
                // com os dois rótulos e devolver tudo, não só os ativos.
                foreach (self::ROTULOS_ATIVO as $rotulo => $valorAtivo) {
                    if (str_starts_with($rotulo, $termoNormalizado)) {
                        $query->orWhere('ativo', $valorAtivo);
                    }
                }
            });
        }
    }

    /**
     * @param  array<int, array{field?: string, dir?: string}>  $ordenacoes
     */
    private function aplicarOrdenacao(Builder $consulta, array $ordenacoes): bool
    {
        $aplicou = false;

        foreach ($ordenacoes as $ordenacao) {
            $expressao = self::COLUNAS_ORDENAVEIS[$ordenacao['field'] ?? ''] ?? null;

            if ($expressao === null) {
                continue;
            }

            $direcao = strtolower((string) ($ordenacao['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
            $consulta->orderByRaw("{$expressao} {$direcao}");
            $aplicou = true;
        }

        return $aplicou;
    }
}

<?php

namespace App\Services;

use App\Contracts\TemRotulo;
use App\Enums\IndicadorInscricaoEstadual;
use App\Enums\RegimeRecolhimento;
use App\Enums\TipoPessoa;
use App\Models\Fornecedor;
use App\Models\FornecedorContato;
use App\Repositories\EstadoRepository;
use App\Repositories\FornecedorRepository;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class FornecedorService
{
    public function __construct(
        private readonly FornecedorRepository $fornecedores,
        private readonly EstadoRepository $estados,
    ) {}

    public function documentoJaCadastrado(string $cnpjCpf, ?int $ignorarId = null): bool
    {
        $documento = $this->normalizarDocumento($cnpjCpf);

        return $this->fornecedores->cnpjCpfCadastrado($documento, $ignorarId);
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    public function listarParaGrid(array $parametros): LengthAwarePaginator
    {
        return $this->fornecedores->paginar($parametros);
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return Collection<int, Fornecedor>
     */
    public function listarParaExportacao(array $parametros): Collection
    {
        return $this->fornecedores->listarParaExportacao($parametros);
    }

    public function excluir(Fornecedor $fornecedor): void
    {
        $this->fornecedores->excluir($fornecedor);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Fornecedor
    {
        return $this->transacaoComDocumentoUnico(function () use ($dados) {
            $fornecedor = $this->fornecedores->criar($this->dadosDoFornecedor($dados, situacaoCnpjAtual: null));

            $this->sincronizarContatos($fornecedor, $dados);

            return $fornecedor;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Fornecedor $fornecedor, array $dados): Fornecedor
    {
        return $this->transacaoComDocumentoUnico(function () use ($fornecedor, $dados) {
            $this->fornecedores->atualizar($fornecedor, $this->dadosDoFornecedor($dados, $fornecedor->situacao_cnpj));

            $fornecedor->contatos()->delete();
            $this->sincronizarContatos($fornecedor, $dados);

            return $fornecedor;
        });
    }

    /**
     * `StoreFornecedorRequest`/`UpdateFornecedorRequest` já barram cnpj_cpf
     * duplicado via `Rule::unique`, mas isso é um SELECT antes desta
     * transação — duas requisições concorrentes com o mesmo documento novo
     * podem passar ambas pela validação e só colidir aqui, na constraint
     * unique do banco. Sem isso, a segunda vira um 500 cru em vez de voltar
     * como o mesmo erro de validação que o usuário já veria no caso comum.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws ValidationException
     */
    private function transacaoComDocumentoUnico(Closure $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'cnpj_cpf' => trans('validation.unique', ['attribute' => trans('validation.attributes.cnpj_cpf')]),
            ]);
        }
    }

    /**
     * Tudo que a view do formulário (criar/editar) precisa — estados para o
     * select de UF, dados iniciais para hidratar o JS e as opções dos demais
     * selects. Um único ponto que o controller chama, em vez de montar esse
     * array ele mesmo a partir de três chamadas separadas ao service.
     *
     * @return array{estados: Collection, dadosIniciais: array<string, mixed>, opcoes: array<string, mixed>}
     */
    public function dadosParaFormulario(Fornecedor $fornecedor): array
    {
        return [
            'estados' => $this->estados->todosOrdenadosPorNome(),
            'dadosIniciais' => $this->dadosIniciais($fornecedor),
            'opcoes' => $this->opcoesFormulario($fornecedor),
        ];
    }

    /**
     * Dados iniciais (já resolvendo old()/valores persistidos) para hidratar
     * o formulário no navegador — ver resources/js/fornecedores/index.js.
     *
     * @return array<string, mixed>
     */
    private function dadosIniciais(Fornecedor $fornecedor): array
    {
        return [
            'tipoPessoa' => old('tipo_pessoa', $fornecedor->tipo_pessoa?->value ?? TipoPessoa::Juridica->value),
            'condominio' => (string) old('condominio', $fornecedor->exists ? (int) $fornecedor->condominio : 0),
            'estadoId' => old('estado_id', $fornecedor->estado_id),
            'cidadeId' => old('cidade_id', $fornecedor->cidade_id),
            'contatoPrincipal' => old('contato_principal', $this->dadosContatoPrincipal($fornecedor)),
            'contatosAdicionais' => old('contatos_adicionais', $this->dadosContatosAdicionais($fornecedor)),
            'fornecedorId' => $fornecedor->id,
        ];
    }

    /**
     * Opções (com rótulo e estado selecionado já resolvidos) para os campos
     * de seleção do formulário.
     *
     * @return array<string, array<int, array{value: string, label: string, selected: bool}>>
     */
    private function opcoesFormulario(Fornecedor $fornecedor): array
    {
        return [
            'indicadorInscricaoEstadual' => $this->opcoesEnum(
                IndicadorInscricaoEstadual::cases(),
                old('indicador_inscricao_estadual', $fornecedor->indicador_inscricao_estadual?->value),
            ),
            'recolhimento' => $this->opcoesEnum(
                RegimeRecolhimento::cases(),
                old('recolhimento', $fornecedor->recolhimento?->value),
            ),
            'ativo' => $this->opcoesAtivo($fornecedor),
        ];
    }

    private function normalizarDocumento(string $cnpjCpf): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $cnpjCpf));
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function dadosDoFornecedor(array $dados, ?string $situacaoCnpjAtual): array
    {
        $dados = array_diff_key($dados, array_flip(['contato_principal', 'contatos_adicionais']));

        if (! empty($dados['observacoes'])) {
            $dados['observacoes'] = $this->sanitizarObservacoes($dados['observacoes']);
        }

        $dados['situacao_cnpj'] = $this->situacaoCnpjParaPersistir($dados['cnpj_cpf'] ?? null, $situacaoCnpjAtual);

        return $dados;
    }

    // HTML do editor Quill: sanitiza contra XSS armazenado no backend, nunca confiar só no
    // client — allowlist de tags em config/purifier.php.
    private function sanitizarObservacoes(string $html): string
    {
        return Purifier::clean($html);
    }

    // situacao_cnpj é readonly só na UI — nunca aceita o que veio no POST, só o que
    // CnpjController::show() guardou na sessão numa consulta real para este CNPJ; sem
    // lookup nesta sessão, mantém o valor já persistido em vez de apagá-lo.
    private function situacaoCnpjParaPersistir(?string $cnpjCpf, ?string $atual): ?string
    {
        $confiavel = $cnpjCpf !== null ? session("situacao_cnpj_verificada.{$cnpjCpf}") : null;

        return $confiavel ?? $atual;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function sincronizarContatos(Fornecedor $fornecedor, array $dados): void
    {
        $principal = $fornecedor->contatos()->create(['principal' => true]);
        $this->criarTelefonesEEmails($principal, $dados['contato_principal'] ?? []);

        foreach ($dados['contatos_adicionais'] ?? [] as $adicional) {
            $contato = $fornecedor->contatos()->create([
                'principal' => false,
                'nome' => $adicional['nome'] ?? null,
                'empresa' => $adicional['empresa'] ?? null,
                'cargo' => $adicional['cargo'] ?? null,
            ]);

            $this->criarTelefonesEEmails($contato, $adicional);
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function criarTelefonesEEmails(FornecedorContato $contato, array $dados): void
    {
        foreach ($dados['telefones'] ?? [] as $telefone) {
            $contato->telefones()->create($telefone);
        }

        foreach ($dados['emails'] ?? [] as $email) {
            $contato->emails()->create($email);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosContatoPrincipal(Fornecedor $fornecedor): array
    {
        $principal = $fornecedor->exists ? $fornecedor->contatos->firstWhere('principal', true) : null;

        return [
            'telefones' => $this->dadosTelefones($principal),
            'emails' => $this->dadosEmails($principal),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dadosContatosAdicionais(Fornecedor $fornecedor): array
    {
        if (! $fornecedor->exists) {
            return [];
        }

        return $fornecedor->contatos->where('principal', false)->map(fn ($contato) => [
            'nome' => $contato->nome,
            'empresa' => $contato->empresa,
            'cargo' => $contato->cargo,
            'telefones' => $this->dadosTelefones($contato),
            'emails' => $this->dadosEmails($contato),
        ])->values()->all();
    }

    /**
     * @return array<int, array{numero: string, tipo: string}>
     */
    private function dadosTelefones(?FornecedorContato $contato): array
    {
        return $contato?->telefones
            ->map(fn ($telefone) => ['numero' => $telefone->numero, 'tipo' => $telefone->tipo->value])
            ->values()->all() ?? [];
    }

    /**
     * @return array<int, array{email: string, tipo: string}>
     */
    private function dadosEmails(?FornecedorContato $contato): array
    {
        return $contato?->emails
            ->map(fn ($email) => ['email' => $email->email, 'tipo' => $email->tipo->value])
            ->values()->all() ?? [];
    }

    /**
     * @param  array<int, TemRotulo&\BackedEnum>  $casos
     * @return array<int, array{value: string, label: string, selected: bool}>
     */
    private function opcoesEnum(array $casos, ?string $valorAtual): array
    {
        return array_map(fn ($caso) => [
            'value' => $caso->value,
            'label' => $caso->label(),
            'selected' => $caso->value === $valorAtual,
        ], $casos);
    }

    /**
     * @return array<int, array{value: string, label: string, selected: bool}>
     */
    private function opcoesAtivo(Fornecedor $fornecedor): array
    {
        $atual = (string) old('ativo', $fornecedor->exists ? (int) $fornecedor->ativo : 1);

        return [
            ['value' => '1', 'label' => 'Sim', 'selected' => $atual === '1'],
            ['value' => '0', 'label' => 'Não', 'selected' => $atual === '0'],
        ];
    }
}

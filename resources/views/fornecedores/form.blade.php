@php
    use App\Enums\TipoPessoa;
@endphp

<x-layout :title="$fornecedor->exists ? 'Fornecedor Editar' : 'Fornecedor Cadastrar'">
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Corrija os campos abaixo:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $fornecedor->exists ? route('fornecedores.update', $fornecedor) : route('fornecedores.store') }}"
        id="form-fornecedor"
        novalidate
    >
        @csrf
        @if ($fornecedor->exists)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label class="form-label d-block">Tipo de Pessoa</label>
            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="tipo_pessoa"
                    id="tipo_pessoa_juridica"
                    value="{{ TipoPessoa::Juridica->value }}"
                    data-tipo-pessoa
                    @checked($dadosIniciais['tipoPessoa'] === TipoPessoa::Juridica->value)
                    @disabled($fornecedor->exists)
                    required
                />
                <label class="form-check-label" for="tipo_pessoa_juridica">Pessoa Jurídica</label>
            </div>
            <div class="form-check form-check-inline">
                <input
                    class="form-check-input"
                    type="radio"
                    name="tipo_pessoa"
                    id="tipo_pessoa_fisica"
                    value="{{ TipoPessoa::Fisica->value }}"
                    data-tipo-pessoa
                    @checked($dadosIniciais['tipoPessoa'] === TipoPessoa::Fisica->value)
                    @disabled($fornecedor->exists)
                    required
                />
                <label class="form-check-label" for="tipo_pessoa_fisica">Pessoa Física</label>
            </div>
            @if ($fornecedor->exists)
                <div class="form-text">O tipo de pessoa não pode ser alterado após o cadastro.</div>
                <input type="hidden" name="tipo_pessoa" value="{{ $dadosIniciais['tipoPessoa'] }}" />
            @endif
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Dados do Fornecedor</h3>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="cnpj_cpf">CNPJ/CPF <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('cnpj_cpf') is-invalid @enderror"
                        id="cnpj_cpf"
                        name="cnpj_cpf"
                        value="{{ old('cnpj_cpf', $fornecedor->cnpj_cpf) }}"
                        data-mascara-documento
                        required
                    />
                    <div class="invalid-feedback" data-feedback-documento-invalido>
                        @error('cnpj_cpf')
                            {{ $message }}
                        @else
                            Documento inválido.
                        @enderror
                    </div>
                    <div class="form-text text-danger" data-feedback-documento-duplicado hidden>
                        Este CNPJ/CPF já está cadastrado.
                    </div>
                    <div class="form-text text-danger" data-feedback-cnpj-nao-encontrado hidden>
                        CNPJ não encontrado na Receita Federal.
                    </div>
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="razao_social">Razão Social <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('razao_social') is-invalid @enderror"
                        id="razao_social"
                        name="razao_social"
                        value="{{ old('razao_social', $fornecedor->razao_social) }}"
                        data-obrigatorio-se-visivel
                    />
                    @error('razao_social')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="nome_fantasia">Nome Fantasia <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('nome_fantasia') is-invalid @enderror"
                        id="nome_fantasia"
                        name="nome_fantasia"
                        value="{{ old('nome_fantasia', $fornecedor->nome_fantasia) }}"
                        data-obrigatorio-se-visivel
                    />
                    @error('nome_fantasia')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="fisica">
                    <label class="form-label" for="nome">Nome <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('nome') is-invalid @enderror"
                        id="nome"
                        name="nome"
                        value="{{ old('nome', $fornecedor->nome) }}"
                        data-obrigatorio-se-visivel
                    />
                    @error('nome')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="fisica">
                    <label class="form-label" for="apelido">Apelido</label>
                    <input
                        type="text"
                        class="form-control"
                        id="apelido"
                        name="apelido"
                        value="{{ old('apelido', $fornecedor->apelido) }}"
                    />
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="indicador_inscricao_estadual">
                        Indicador de Inscrição Estadual
                    </label>
                    <select
                        class="form-select"
                        id="indicador_inscricao_estadual"
                        name="indicador_inscricao_estadual"
                        data-habilita="inscricao_estadual"
                        data-habilita-exceto="nao_contribuinte"
                    >
                        <option value="">Selecione</option>
                        @foreach ($opcoes['indicadorInscricaoEstadual'] as $opcao)
                            <option value="{{ $opcao['value'] }}" @selected($opcao['selected'])>{{ $opcao['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="inscricao_estadual">Inscrição Estadual</label>
                    <input
                        type="text"
                        class="form-control"
                        id="inscricao_estadual"
                        name="inscricao_estadual"
                        value="{{ old('inscricao_estadual', $fornecedor->inscricao_estadual) }}"
                    />
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="inscricao_municipal">Inscrição Municipal</label>
                    <input
                        type="text"
                        class="form-control"
                        id="inscricao_municipal"
                        name="inscricao_municipal"
                        value="{{ old('inscricao_municipal', $fornecedor->inscricao_municipal) }}"
                    />
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="situacao_cnpj">Situação CNPJ</label>
                    <input
                        type="text"
                        class="form-control bg-body-secondary"
                        id="situacao_cnpj"
                        name="situacao_cnpj"
                        value="{{ old('situacao_cnpj', $fornecedor->situacao_cnpj) }}"
                        readonly
                    />
                    <div class="form-text">Preenchido automaticamente pela consulta do CNPJ.</div>
                </div>

                <div class="col-md-4" data-visivel-se-pessoa="juridica">
                    <label class="form-label" for="recolhimento">Recolhimento <x-obrigatorio /></label>
                    <select
                        class="form-select @error('recolhimento') is-invalid @enderror"
                        id="recolhimento"
                        name="recolhimento"
                        data-obrigatorio-se-visivel
                    >
                        <option value="">Selecione</option>
                        @foreach ($opcoes['recolhimento'] as $opcao)
                            <option value="{{ $opcao['value'] }}" @selected($opcao['selected'])>{{ $opcao['label'] }}</option>
                        @endforeach
                    </select>
                    @error('recolhimento')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="ativo">Ativo <x-obrigatorio /></label>
                    <select class="form-select" id="ativo" name="ativo" required>
                        @foreach ($opcoes['ativo'] as $opcao)
                            <option value="{{ $opcao['value'] }}" @selected($opcao['selected'])>{{ $opcao['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Contato Principal</h3>
            </div>
            <div class="card-body">
                <label class="form-label">Telefones <x-obrigatorio /></label>
                <div id="telefones-principal" class="mb-3"></div>

                <label class="form-label d-block">E-mails</label>
                <div id="emails-principal"></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Contatos Adicionais</h3>
                    <button type="button" class="btn btn-sm btn-success" data-adicionar-contato>
                        <i class="bi bi-plus-lg"></i> Adicionar Contato
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-secondary text-center py-4 mb-0" data-contatos-vazio>Não há contatos.</p>
                <div id="contatos-adicionais"></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Dados do Endereço</h3>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="cep">CEP <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('cep') is-invalid @enderror"
                        id="cep"
                        name="cep"
                        value="{{ old('cep', $fornecedor->cep) }}"
                        data-mascara-cep
                        required
                    />
                    @error('cep')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-danger" data-feedback-cep hidden>CEP não encontrado.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="logradouro">Logradouro <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('logradouro') is-invalid @enderror"
                        id="logradouro"
                        name="logradouro"
                        value="{{ old('logradouro', $fornecedor->logradouro) }}"
                        required
                    />
                    @error('logradouro')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="numero">Número <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('numero') is-invalid @enderror"
                        id="numero"
                        name="numero"
                        value="{{ old('numero', $fornecedor->numero) }}"
                        required
                    />
                    @error('numero')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="complemento">Complemento</label>
                    <input
                        type="text"
                        class="form-control"
                        id="complemento"
                        name="complemento"
                        value="{{ old('complemento', $fornecedor->complemento) }}"
                    />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="bairro">Bairro <x-obrigatorio /></label>
                    <input
                        type="text"
                        class="form-control @error('bairro') is-invalid @enderror"
                        id="bairro"
                        name="bairro"
                        value="{{ old('bairro', $fornecedor->bairro) }}"
                        required
                    />
                    @error('bairro')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="ponto_referencia">Ponto de Referência</label>
                    <input
                        type="text"
                        class="form-control"
                        id="ponto_referencia"
                        name="ponto_referencia"
                        value="{{ old('ponto_referencia', $fornecedor->ponto_referencia) }}"
                    />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="estado_id">UF <x-obrigatorio /></label>
                    <select
                        class="form-select @error('estado_id') is-invalid @enderror"
                        id="estado_id"
                        name="estado_id"
                        required
                    >
                        <option value="">Selecione</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->id }}" @selected((string) $dadosIniciais['estadoId'] === (string) $estado->id)>
                                {{ $estado->sigla }} - {{ $estado->nome }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cidade_id">Cidade <x-obrigatorio /></label>
                    <select
                        class="form-select @error('cidade_id') is-invalid @enderror"
                        id="cidade_id"
                        name="cidade_id"
                        required
                        disabled
                    >
                        <option value="">Selecione o estado primeiro</option>
                    </select>
                    @error('cidade_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Condomínio <x-obrigatorio /></label>
                    <div class="form-check form-check-inline">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="condominio"
                            id="condominio_sim"
                            value="1"
                            data-condominio
                            @checked($dadosIniciais['condominio'] === '1')
                            required
                        />
                        <label class="form-check-label" for="condominio_sim">Sim</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input
                            class="form-check-input"
                            type="radio"
                            name="condominio"
                            id="condominio_nao"
                            value="0"
                            data-condominio
                            @checked($dadosIniciais['condominio'] === '0')
                            required
                        />
                        <label class="form-check-label" for="condominio_nao">Não</label>
                    </div>
                </div>
                <div class="col-md-4" data-visivel-se-condominio>
                    <label class="form-label" for="condominio_endereco">Endereço do Condomínio</label>
                    <input
                        type="text"
                        class="form-control"
                        id="condominio_endereco"
                        name="condominio_endereco"
                        value="{{ old('condominio_endereco', $fornecedor->condominio_endereco) }}"
                        data-obrigatorio-se-visivel
                    />
                </div>
                <div class="col-md-3" data-visivel-se-condominio>
                    <label class="form-label" for="condominio_numero">Número do Condomínio</label>
                    <input
                        type="text"
                        class="form-control"
                        id="condominio_numero"
                        name="condominio_numero"
                        value="{{ old('condominio_numero', $fornecedor->condominio_numero) }}"
                        data-obrigatorio-se-visivel
                    />
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Observações</h3>
            </div>
            <div class="card-body">
                <div id="editor-observacoes" style="min-height: 150px"></div>
                <textarea name="observacoes" id="observacoes" hidden>{{ old('observacoes', $fornecedor->observacoes) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-lg me-1"></i>
                {{ $fornecedor->exists ? 'Salvar Alterações' : 'Cadastrar' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script type="application/json" id="dados-fornecedor-form">{!! json_encode(
            $dadosIniciais,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
        ) !!}</script>
        @vite(['resources/js/fornecedores/index.js'])
    @endpush
</x-layout>

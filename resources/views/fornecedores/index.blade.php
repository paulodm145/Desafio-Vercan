<x-layout title="Fornecedores">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h3 class="card-title mb-0">Listagem</h3>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 16rem">
                                <span class="input-group-text">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                </span>
                                <input
                                    id="busca-global"
                                    type="search"
                                    class="form-control"
                                    placeholder="Buscar fornecedor…"
                                    aria-label="Buscar fornecedor"
                                />
                            </div>
                            <button
                                type="button"
                                id="exportar-excel"
                                class="btn btn-outline-success btn-sm text-nowrap"
                                data-url="{{ route('fornecedores.exportar-excel') }}"
                            >
                                <i class="bi bi-file-earmark-excel me-1"></i>
                                Excel
                            </button>
                            <button
                                type="button"
                                id="exportar-pdf"
                                class="btn btn-outline-danger btn-sm text-nowrap"
                                data-url="{{ route('fornecedores.exportar-pdf') }}"
                            >
                                <i class="bi bi-file-earmark-pdf me-1"></i>
                                PDF
                            </button>
                            <a href="{{ route('fornecedores.create') }}" class="btn btn-success btn-sm text-nowrap">
                                <i class="bi bi-plus-lg me-1"></i>
                                Novo Fornecedor
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="fornecedores-table" data-url="{{ route('fornecedores.dados') }}"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/fornecedores-grid/index.js'])
    @endpush
</x-layout>

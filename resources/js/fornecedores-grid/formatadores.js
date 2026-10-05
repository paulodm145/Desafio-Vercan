// CNPJ/CPF chega limpo do backend (sem pontuação) — a máscara é só exibição.
export function formatarDocumento(valor) {
    if (!valor) {
        return '';
    }

    const texto = String(valor);

    if (texto.length === 11) {
        return texto.replace(/(.{3})(.{3})(.{3})(.{2})/, '$1.$2.$3-$4');
    }

    if (texto.length === 14) {
        return texto.replace(/(.{2})(.{3})(.{3})(.{4})(.{2})/, '$1.$2.$3/$4-$5');
    }

    return texto;
}

export function formatarDocumentoCelula(cell) {
    return formatarDocumento(cell.getValue());
}

export function formatarBadgeAtivo(cell) {
    const ativo = cell.getValue();
    const classe = ativo ? 'text-bg-success' : 'text-bg-danger';
    const texto = ativo ? 'Ativo' : 'Inativo';

    return `<span class="badge ${classe}">${texto}</span>`;
}

export function formatarAcoes(cell) {
    const dados = cell.getRow().getData();

    return `
        <a href="${dados.editar_url}" class="btn btn-sm btn-outline-primary me-1" title="Editar fornecedor" aria-label="Editar fornecedor">
            <i class="bi bi-pencil"></i>
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir fornecedor" aria-label="Excluir fornecedor">
            <i class="bi bi-trash"></i>
        </button>
    `;
}

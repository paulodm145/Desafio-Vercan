// Escapa texto antes de interpolar em innerHTML/template string. Usar sempre
// que um dado vindo do backend (potencialmente digitado por um usuário) for
// montar HTML via string em vez de .value/.textContent — ver uso em
// fornecedores-grid/exclusao.js e fornecedores/integracao.js.
export function escapeHtml(valor) {
    const div = document.createElement('div');
    div.textContent = valor ?? '';

    return div.innerHTML;
}

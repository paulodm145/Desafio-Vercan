import Swal from 'sweetalert2';
import { escapeHtml } from '../shared/escape-html';
import { mostrarToast } from './notificacoes';

export async function excluirFornecedor(row) {
    const dados = row.getData();

    const resultado = await Swal.fire({
        title: 'Excluir fornecedor?',
        html: `Tem certeza que deseja excluir <strong>${escapeHtml(dados.razao_social_nome)}</strong>? Essa ação não pode ser desfeita.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
    });

    if (!resultado.isConfirmed) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]').content;

    const resposta = await fetch(dados.excluir_url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
    });

    if (!resposta.ok) {
        await Swal.fire({
            icon: 'error',
            title: 'Não foi possível excluir',
            text: 'Tente novamente em instantes.',
        });

        return;
    }

    row.delete();
    mostrarToast('Fornecedor excluído com sucesso.');
}

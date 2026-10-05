import { Toast } from 'bootstrap';

function containerToast() {
    let container = document.querySelector('.toast-container');

    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }

    return container;
}

// Toast no padrão AdminLTE/Bootstrap (UI/general.html): markup .toast-container
// + .toast fixo no canto da tela, não um alert() nem o toast do SweetAlert.
export function mostrarToast(mensagem, variante = 'success') {
    const elemento = document.createElement('div');
    elemento.className = `toast text-bg-${variante}`;
    elemento.setAttribute('role', 'alert');
    elemento.setAttribute('aria-live', 'assertive');
    elemento.setAttribute('aria-atomic', 'true');
    elemento.innerHTML = `
        <div class="toast-header">
            <strong class="me-auto">Vercan</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
        <div class="toast-body">${mensagem}</div>
    `;

    containerToast().appendChild(elemento);
    elemento.addEventListener('hidden.bs.toast', () => elemento.remove());

    new Toast(elemento, { delay: 4000 }).show();
}

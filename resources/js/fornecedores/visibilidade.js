function alternarCamposPessoa() {
    const tipo = document.querySelector('[data-tipo-pessoa]:checked')?.value;

    document.querySelectorAll('[data-visivel-se-pessoa]').forEach((bloco) => {
        const visivel = bloco.dataset.visivelSePessoa === tipo;

        bloco.hidden = !visivel;
        bloco.querySelectorAll('[data-obrigatorio-se-visivel]').forEach((campo) => {
            campo.required = visivel;
        });
    });
}

function alternarCamposCondominio() {
    const valor = document.querySelector('[data-condominio]:checked')?.value;
    const visivel = valor === '1';

    document.querySelectorAll('[data-visivel-se-condominio]').forEach((bloco) => {
        bloco.hidden = !visivel;
        bloco.querySelectorAll('[data-obrigatorio-se-visivel]').forEach((campo) => {
            campo.required = visivel;
        });
    });
}

function inicializarCamposHabilitadosPorSelecao() {
    document.querySelectorAll('[data-habilita]').forEach((origem) => {
        const campo = document.getElementById(origem.dataset.habilita);
        const valoresQueNaoHabilitam = ['', ...(origem.dataset.habilitaExceto?.split(',') ?? [])];
        const habilitar = () => !valoresQueNaoHabilitam.includes(origem.value);

        // No carregamento inicial só reflete o estado — nunca apaga um valor
        // já persistido. Limpar o campo ao desabilitar só faz sentido numa
        // mudança feita pelo próprio usuário.
        campo.disabled = !habilitar();

        origem.addEventListener('change', () => {
            campo.disabled = !habilitar();

            if (campo.disabled) {
                campo.value = '';
            }
        });
    });
}

export function inicializarVisibilidade() {
    document.querySelectorAll('[data-tipo-pessoa]').forEach((radio) => {
        radio.addEventListener('change', alternarCamposPessoa);
    });
    document.querySelectorAll('[data-condominio]').forEach((radio) => {
        radio.addEventListener('change', alternarCamposCondominio);
    });

    alternarCamposPessoa();
    alternarCamposCondominio();
    inicializarCamposHabilitadosPorSelecao();
}

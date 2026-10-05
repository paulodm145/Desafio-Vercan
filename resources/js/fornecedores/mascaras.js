import IMask from 'imask';

const DEFINICAO_ALFANUMERICO = { '*': /[0-9A-Za-z]/ };

export function aplicarMascaraDocumento(input) {
    if (!input) return null;

    return IMask(input, {
        mask: [
            { mask: '000.000.000-00' },
            {
                mask: '**.***.***/****-00',
                definitions: DEFINICAO_ALFANUMERICO,
                prepare: (valor) => valor.toUpperCase(),
            },
        ],
        dispatch(apendado, mascaraDinamica) {
            const valorBruto = (mascaraDinamica.unmaskedValue + apendado).replace(/[^0-9A-Za-z]/g, '');

            return valorBruto.length > 11 ? mascaraDinamica.compiledMasks[1] : mascaraDinamica.compiledMasks[0];
        },
    });
}

export function aplicarMascaraTelefone(input) {
    if (!input) return null;

    return IMask(input, {
        mask: [{ mask: '(00) 0000-0000' }, { mask: '(00) 00000-0000' }],
        dispatch(apendado, mascaraDinamica) {
            const valorBruto = (mascaraDinamica.unmaskedValue + apendado).replace(/\D/g, '');

            return valorBruto.length > 10 ? mascaraDinamica.compiledMasks[1] : mascaraDinamica.compiledMasks[0];
        },
    });
}

export function aplicarMascaraCep(input) {
    if (!input) return null;

    return IMask(input, { mask: '00000-000' });
}

// Mesmos algoritmos de App\Rules\CpfValido e App\Rules\CnpjValido — mantém a
// validação (e a decisão de dar prosseguimento às buscas via AJAX) coerente
// entre o que o usuário vê e o que o backend aceita.

function somenteAlfanumerico(valor) {
    return (valor ?? '').toUpperCase().replace(/[^0-9A-Z]/g, '');
}

export function cpfValido(valor) {
    const cpf = (valor ?? '').replace(/\D/g, '');

    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    for (let posicao = 9; posicao <= 10; posicao++) {
        let soma = 0;

        for (let i = 0; i < posicao; i++) {
            soma += Number(cpf[i]) * (posicao + 1 - i);
        }

        const resto = soma % 11;
        const digitoCalculado = resto < 2 ? 0 : 11 - resto;

        if (digitoCalculado !== Number(cpf[posicao])) {
            return false;
        }
    }

    return true;
}

const PESOS_DV1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
const PESOS_DV2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

function calcularDigitoCnpj(base, pesos) {
    let soma = 0;

    for (let i = 0; i < base.length; i++) {
        soma += (base.charCodeAt(i) - 48) * pesos[i];
    }

    const resto = soma % 11;

    return resto < 2 ? 0 : 11 - resto;
}

export function cnpjValido(valor) {
    const cnpj = somenteAlfanumerico(valor);

    if (!/^[0-9A-Z]{12}\d{2}$/.test(cnpj)) {
        return false;
    }

    const base = cnpj.slice(0, 12);
    const dv1 = calcularDigitoCnpj(base, PESOS_DV1);
    const dv2 = calcularDigitoCnpj(base + dv1, PESOS_DV2);

    return `${dv1}${dv2}` === cnpj.slice(12, 14);
}

export function documentoValido(valor, tipoPessoa) {
    return tipoPessoa === 'fisica' ? cpfValido(valor) : cnpjValido(valor);
}

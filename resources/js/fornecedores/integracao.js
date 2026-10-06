import { escapeHtml } from '../shared/escape-html';
import { documentoValido } from './validacao-documento';

const MENSAGEM_ERRO_REDE = 'Não foi possível conectar ao servidor. Tente novamente.';

// Sentinel (não um valor de dados real) para distinguir "a API respondeu que
// não encontrou nada" de "a requisição nem chegou a ir e voltar" — os dois
// casos não podem usar o mesmo `null`, senão uma falha de rede silenciosamente
// vira "CNPJ não encontrado"/"CEP não encontrado" para o usuário.
const ERRO_REDE = Symbol('erro-de-rede');

async function buscarJson(url) {
    let resposta;

    try {
        resposta = await fetch(url, { headers: { Accept: 'application/json' } });
    } catch {
        // fetch() rejeita a Promise em falha de rede/DNS/CORS (diferente de uma
        // resposta HTTP de erro, tratada abaixo). Sem este catch, a exceção
        // subia pelos `await` de quem chama e abortava o resto do handler de
        // blur em silêncio — ex.: a verificação de documento duplicado falhando
        // impedia a busca na ReceitaWS de sequer rodar.
        return ERRO_REDE;
    }

    // 404 aqui é usado deliberadamente pelos endpoints de proxy (/ceps, /cnpjs)
    // pra sinalizar "não encontrado" via HTTP, com o corpo {encontrado:false} —
    // qualquer outro status (ex.: 503 de serviço externo indisponível) é tratado
    // como falha, não silenciosamente como "não encontrado".
    if (resposta.ok || resposta.status === 404) {
        return resposta.json();
    }

    return ERRO_REDE;
}

// Guarda contra respostas fora de ordem: se o usuário dispara duas buscas
// (ex.: digita um CEP, corrige, blur de novo) e a resposta da primeira chega
// DEPOIS da segunda, sem isso a última a CHEGAR vence em vez da última a ser
// DISPARADA, podendo preencher o formulário com dados de um CEP/CNPJ
// diferente do que está no campo. Cada fluxo (endereço, cidades, documento)
// tem sua própria sequência — só a chamada mais recente daquele fluxo tem
// permissão de aplicar o resultado no DOM.
function criarGuardaDeCorrida() {
    let atual = 0;

    return {
        novoToken: () => ++atual,
        ehMaisRecente: (token) => token === atual,
    };
}

const guardaEndereco = criarGuardaDeCorrida();
const guardaCidades = criarGuardaDeCorrida();
const guardaDocumento = criarGuardaDeCorrida();

// `mensagem`, quando informada, sobrescreve o texto do elemento (usado para o
// aviso de erro de rede) — o texto original (escrito no Blade) é lembrado em
// `data-mensagem-original` e restaurado da próxima vez que o feedback for
// mostrado sem uma mensagem customizada, pra um erro de rede não deixar o
// aviso de "não encontrado"/"duplicado" com o texto errado permanentemente.
function mostrarFeedback(elemento, exibir, mensagem = null) {
    if (!elemento) {
        return;
    }

    if (mensagem !== null) {
        elemento.dataset.mensagemOriginal ??= elemento.textContent;
        elemento.textContent = mensagem;
    } else if (elemento.dataset.mensagemOriginal) {
        elemento.textContent = elemento.dataset.mensagemOriginal;
    }

    elemento.hidden = !exibir;
}

export async function carregarCidades(estadoId, cidadeSelecionadaId = null) {
    const campoCidade = document.getElementById('cidade_id');
    const token = guardaCidades.novoToken();

    if (!estadoId) {
        campoCidade.innerHTML = '<option value="">Selecione o estado primeiro</option>';
        campoCidade.disabled = true;

        return;
    }

    const resultado = await buscarJson(`/estados/${estadoId}/cidades`);

    if (!guardaCidades.ehMaisRecente(token)) {
        return;
    }

    if (resultado === ERRO_REDE) {
        campoCidade.innerHTML = `<option value="">${MENSAGEM_ERRO_REDE}</option>`;
        campoCidade.disabled = true;

        return;
    }

    // Array.isArray, não só `?? []`: um 404 de route-model-binding (estado_id
    // inválido — não deveria acontecer vindo do <select>, mas defende contra
    // chamada manual) tem corpo {message:...} do próprio Laravel, não um array.
    const cidades = Array.isArray(resultado) ? resultado : [];

    // cidade.nome vem do LocalidadeSeeder (dado confiável, não editável por
    // usuário) hoje — escapado mesmo assim, pelo mesmo motivo de qualquer
    // outro ponto que interpola em innerHTML: se esse dado algum dia passar a
    // ser editável, não quebra o mesmo jeito que contatos.js/exclusao.js já
    // corrigiram.
    campoCidade.innerHTML =
        '<option value="">Selecione</option>' +
        cidades.map((cidade) => `<option value="${cidade.id}">${escapeHtml(cidade.nome)}</option>`).join('');
    campoCidade.disabled = false;

    if (cidadeSelecionadaId) {
        campoCidade.value = cidadeSelecionadaId;
    }
}

async function preencherEnderecoPorCep(cep) {
    const feedback = document.querySelector('[data-feedback-cep]');
    const token = guardaEndereco.novoToken();
    const dados = await buscarJson(`/ceps/${cep.replace(/\D/g, '')}`);

    if (!guardaEndereco.ehMaisRecente(token)) {
        return;
    }

    if (dados === ERRO_REDE) {
        mostrarFeedback(feedback, true, MENSAGEM_ERRO_REDE);

        return;
    }

    if (!dados?.encontrado) {
        mostrarFeedback(feedback, true);

        return;
    }

    mostrarFeedback(feedback, false);

    document.getElementById('logradouro').value = dados.logradouro ?? '';
    document.getElementById('bairro').value = dados.bairro ?? '';

    if (dados.complemento) {
        document.getElementById('complemento').value = dados.complemento;
    }

    if (dados.estado) {
        document.getElementById('estado_id').value = dados.estado.id;
        await carregarCidades(dados.estado.id, dados.cidade?.id ?? null);
    }
}

export function inicializarIntegracaoCep() {
    document.getElementById('cep').addEventListener('blur', (evento) => {
        const cep = evento.target.value.replace(/\D/g, '');

        if (cep.length === 8) {
            preencherEnderecoPorCep(cep);
        }
    });
}

async function buscarReceitaWs(documento, ehAindaRelevante) {
    const feedback = document.querySelector('[data-feedback-cnpj-nao-encontrado]');
    const dados = await buscarJson(`/cnpjs/${documento}`);

    if (!ehAindaRelevante()) {
        return;
    }

    if (dados === ERRO_REDE) {
        mostrarFeedback(feedback, true, MENSAGEM_ERRO_REDE);

        return;
    }

    if (!dados?.encontrado) {
        mostrarFeedback(feedback, true);

        return;
    }

    mostrarFeedback(feedback, false);

    document.getElementById('razao_social').value = dados.razao_social ?? '';
    document.getElementById('nome_fantasia').value = dados.nome_fantasia ?? '';
    document.getElementById('situacao_cnpj').value = dados.situacao_cnpj ?? '';

    if (dados.cep) {
        document.getElementById('cep').value = dados.cep;
        await preencherEnderecoPorCep(dados.cep);
    }
}

async function verificarDocumentoDuplicado(documento, fornecedorId, ehAindaRelevante) {
    const feedback = document.querySelector('[data-feedback-documento-duplicado]');
    const parametros = new URLSearchParams({ documento });

    if (fornecedorId) {
        parametros.set('fornecedor_id', fornecedorId);
    }

    const dados = await buscarJson(`/fornecedores/verificar-documento?${parametros}`);

    if (!ehAindaRelevante()) {
        return;
    }

    if (dados === ERRO_REDE) {
        mostrarFeedback(feedback, true, MENSAGEM_ERRO_REDE);

        return;
    }

    mostrarFeedback(feedback, Boolean(dados?.cadastrado));
}

// Valida o CPF/CNPJ no navegador antes de disparar qualquer requisição: um
// documento com formato ou dígito verificador errado nunca chega a consultar
// a Receita WS nem a checar duplicidade — só mostra que está inválido.
export function inicializarValidacaoEIntegracaoDoDocumento(fornecedorId) {
    const campo = document.getElementById('cnpj_cpf');
    const feedbackDuplicado = document.querySelector('[data-feedback-documento-duplicado]');
    const feedbackNaoEncontrado = document.querySelector('[data-feedback-cnpj-nao-encontrado]');

    campo.addEventListener('blur', async () => {
        // Token reivindicado antes de qualquer `await` (inclusive nos retornos
        // antecipados abaixo) — se o usuário limpar/trocar o documento enquanto
        // uma busca anterior ainda está em voo, a resposta atrasada dela se
        // reconhece como obsoleta e não sobrescreve o que está em tela agora.
        const token = guardaDocumento.novoToken();
        const ehAindaRelevante = () => guardaDocumento.ehMaisRecente(token);

        const documento = campo.value.replace(/[^0-9A-Za-z]/g, '');

        if (documento === '') {
            campo.classList.remove('is-invalid');
            mostrarFeedback(feedbackDuplicado, false);
            mostrarFeedback(feedbackNaoEncontrado, false);

            return;
        }

        const tipoPessoa = document.querySelector('[data-tipo-pessoa]:checked')?.value;
        const valido = documentoValido(documento, tipoPessoa);

        campo.classList.toggle('is-invalid', !valido);

        if (!valido) {
            mostrarFeedback(feedbackDuplicado, false);
            mostrarFeedback(feedbackNaoEncontrado, false);

            return;
        }

        await verificarDocumentoDuplicado(documento, fornecedorId, ehAindaRelevante);

        if (tipoPessoa === 'juridica' && documento.length === 14) {
            await buscarReceitaWs(documento, ehAindaRelevante);
        }
    });
}

export function inicializarIntegracaoEstadoCidade(dadosIniciais) {
    const campoEstado = document.getElementById('estado_id');

    campoEstado.addEventListener('change', () => carregarCidades(campoEstado.value));

    if (dadosIniciais.estadoId) {
        carregarCidades(dadosIniciais.estadoId, dadosIniciais.cidadeId);
    }
}

import { aplicarMascaraTelefone } from './mascaras';

const TIPOS_TELEFONE = [
    { valor: 'residencial', rotulo: 'Residencial' },
    { valor: 'comercial', rotulo: 'Comercial' },
    { valor: 'celular', rotulo: 'Celular' },
];

const TIPOS_EMAIL = [
    { valor: 'pessoal', rotulo: 'Pessoal' },
    { valor: 'comercial', rotulo: 'Comercial' },
    { valor: 'outro', rotulo: 'Outro' },
];

function criarOpcoes(tipos, valorSelecionado) {
    return tipos
        .map(({ valor, rotulo }) => `<option value="${valor}" ${valor === valorSelecionado ? 'selected' : ''}>${rotulo}</option>`)
        .join('');
}

// Toda lista mantém sempre ao menos uma linha: o botão de remover da última
// linha restante fica desabilitado em vez de escondido, para o usuário
// entender por que não pode removê-la.
function atualizarBotoesRemover(container) {
    const linhas = container.children;

    Array.from(linhas).forEach((linha) => {
        const botaoRemover = linha.querySelector('[data-acao="remover"]');

        if (botaoRemover) {
            botaoRemover.disabled = linhas.length <= 1;
        }
    });
}

function criarBotoesLadoALado({ aoAdicionar, aoRemover, rotuloAdicionar, rotuloRemover }) {
    const grupo = document.createElement('div');
    grupo.className = 'btn-group';
    grupo.setAttribute('role', 'group');
    grupo.innerHTML = `
        <button type="button" class="btn btn-outline-primary" aria-label="${rotuloAdicionar}">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button type="button" class="btn btn-outline-danger" data-acao="remover" aria-label="${rotuloRemover}">
            <i class="bi bi-trash"></i>
        </button>
    `;

    const [botaoAdicionar, botaoRemover] = grupo.querySelectorAll('button');
    botaoAdicionar.addEventListener('click', aoAdicionar);
    botaoRemover.addEventListener('click', aoRemover);

    return grupo;
}

function criarLinhaTelefone(nomeBase, valores, gerenciador) {
    const linha = document.createElement('div');
    linha.className = 'row g-2 mb-2 align-items-center';
    linha.innerHTML = `
        <div class="col">
            <input type="text" class="form-control" name="${nomeBase}[numero]" placeholder="Telefone" required>
        </div>
        <div class="col">
            <select class="form-select" name="${nomeBase}[tipo]" required>
                <option value="">Tipo</option>
                ${criarOpcoes(TIPOS_TELEFONE, valores.tipo)}
            </select>
        </div>
        <div class="col-auto"></div>
    `;

    const campoNumero = linha.querySelector('input');
    campoNumero.value = valores.numero ?? '';
    aplicarMascaraTelefone(campoNumero);

    linha.querySelector('.col-auto').appendChild(
        criarBotoesLadoALado({
            rotuloAdicionar: 'Adicionar telefone',
            rotuloRemover: 'Remover telefone',
            aoAdicionar: () => gerenciador.adicionar(),
            aoRemover: () => gerenciador.remover(linha),
        }),
    );

    return linha;
}

function criarLinhaEmail(nomeBase, valores, gerenciador) {
    const linha = document.createElement('div');
    linha.className = 'row g-2 mb-2 align-items-center';
    linha.innerHTML = `
        <div class="col">
            <input type="email" class="form-control" name="${nomeBase}[email]" placeholder="E-mail" required>
        </div>
        <div class="col">
            <select class="form-select" name="${nomeBase}[tipo]" required>
                <option value="">Tipo</option>
                ${criarOpcoes(TIPOS_EMAIL, valores.tipo)}
            </select>
        </div>
        <div class="col-auto"></div>
    `;

    // Valor atribuído via .value, não interpolado no innerHTML: evita XSS
    // armazenado se o e-mail salvo contiver algo como `"><img onerror=...>`.
    linha.querySelector('input').value = valores.email ?? '';

    linha.querySelector('.col-auto').appendChild(
        criarBotoesLadoALado({
            rotuloAdicionar: 'Adicionar e-mail',
            rotuloRemover: 'Remover e-mail',
            aoAdicionar: () => gerenciador.adicionar(),
            aoRemover: () => gerenciador.remover(linha),
        }),
    );

    return linha;
}

function criarGerenciadorLista(container, campoPrefixo, criarLinha) {
    let proximoIndice = 0;

    const gerenciador = {
        adicionar(valores = {}) {
            const linha = criarLinha(`${campoPrefixo}[${proximoIndice++}]`, valores, gerenciador);
            container.appendChild(linha);
            atualizarBotoesRemover(container);

            return linha;
        },
        remover(linha) {
            if (container.children.length <= 1) {
                return;
            }

            linha.remove();
            atualizarBotoesRemover(container);
        },
    };

    return gerenciador;
}

function criarListaTelefones(container, campoPrefixo, telefonesIniciais) {
    const gerenciador = criarGerenciadorLista(container, campoPrefixo, criarLinhaTelefone);

    if (telefonesIniciais.length > 0) {
        telefonesIniciais.forEach((telefone) => gerenciador.adicionar(telefone));
    } else {
        gerenciador.adicionar();
    }

    return gerenciador;
}

function criarListaEmails(container, campoPrefixo, emailsIniciais) {
    const gerenciador = criarGerenciadorLista(container, campoPrefixo, criarLinhaEmail);

    if (emailsIniciais.length > 0) {
        emailsIniciais.forEach((email) => gerenciador.adicionar(email));
    } else {
        gerenciador.adicionar();
    }

    return gerenciador;
}

function criarBlocoContatoAdicional(indice, dados = {}, aoRemover) {
    const prefixo = `contatos_adicionais[${indice}]`;
    const bloco = document.createElement('div');
    bloco.className = 'border rounded p-3 mb-3';
    bloco.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fs-6 mb-0">Contato adicional</h4>
            <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Remover contato adicional">
                <i class="bi bi-trash"></i> Remover
            </button>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Nome</label>
                <input type="text" class="form-control" name="${prefixo}[nome]" data-campo="nome">
            </div>
            <div class="col-md-4">
                <label class="form-label">Empresa</label>
                <input type="text" class="form-control" name="${prefixo}[empresa]" data-campo="empresa">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cargo</label>
                <input type="text" class="form-control" name="${prefixo}[cargo]" data-campo="cargo">
            </div>
        </div>
        <label class="form-label">Telefones</label>
        <div data-container-telefones></div>
        <label class="form-label d-block">E-mails</label>
        <div data-container-emails></div>
    `;

    // Valores atribuídos via .value, não interpolados no innerHTML: evita XSS
    // armazenado se nome/empresa/cargo salvos contiverem markup malicioso.
    bloco.querySelector('[data-campo="nome"]').value = dados.nome ?? '';
    bloco.querySelector('[data-campo="empresa"]').value = dados.empresa ?? '';
    bloco.querySelector('[data-campo="cargo"]').value = dados.cargo ?? '';

    criarListaTelefones(bloco.querySelector('[data-container-telefones]'), `${prefixo}[telefones]`, dados.telefones ?? []);
    criarListaEmails(bloco.querySelector('[data-container-emails]'), `${prefixo}[emails]`, dados.emails ?? []);

    bloco.querySelector('.btn-outline-danger').addEventListener('click', () => {
        bloco.remove();
        aoRemover();
    });

    return bloco;
}

export function inicializarContatos(dadosIniciais) {
    criarListaTelefones(
        document.getElementById('telefones-principal'),
        'contato_principal[telefones]',
        dadosIniciais.contatoPrincipal?.telefones ?? [],
    );
    criarListaEmails(
        document.getElementById('emails-principal'),
        'contato_principal[emails]',
        dadosIniciais.contatoPrincipal?.emails ?? [],
    );

    const containerContatos = document.getElementById('contatos-adicionais');
    const avisoVazio = document.querySelector('[data-contatos-vazio]');
    let proximoIndiceContato = 0;

    function atualizarAvisoVazio() {
        avisoVazio.hidden = containerContatos.children.length > 0;
    }

    function adicionarContato(dados = {}) {
        const bloco = criarBlocoContatoAdicional(proximoIndiceContato++, dados, atualizarAvisoVazio);
        containerContatos.appendChild(bloco);
        atualizarAvisoVazio();
    }

    (dadosIniciais.contatosAdicionais ?? []).forEach((contato) => adicionarContato(contato));
    atualizarAvisoVazio();

    document.querySelector('[data-adicionar-contato]').addEventListener('click', () => adicionarContato());
}

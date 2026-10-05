import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_bootstrap5.min.css';

import { formatarAcoes, formatarBadgeAtivo, formatarDocumentoCelula } from './formatadores';
import { langPtBr } from './locale';
import { excluirFornecedor } from './exclusao';

const rotuloRodape = (texto) => () => texto;

function criarColunas() {
    return [
        {
            title: 'Razão Social / Nome',
            field: 'razao_social_nome',
            bottomCalc: rotuloRodape('Razão Social / Nome'),
        },
        {
            title: 'Nome Fantasia / Apelido',
            field: 'nome_fantasia_apelido',
            bottomCalc: rotuloRodape('Nome Fantasia / Apelido'),
        },
        {
            title: 'CNPJ/CPF',
            field: 'cnpj_cpf',
            formatter: formatarDocumentoCelula,
            bottomCalc: rotuloRodape('CNPJ/CPF'),
        },
        {
            title: 'Ativo',
            field: 'ativo',
            hozAlign: 'center',
            headerHozAlign: 'center',
            formatter: formatarBadgeAtivo,
            bottomCalc: rotuloRodape('Ativo'),
        },
        {
            title: 'Ações',
            field: 'id',
            headerSort: false,
            hozAlign: 'center',
            headerHozAlign: 'center',
            formatter: formatarAcoes,
            cellClick: (evento, celula) => {
                const botaoExcluir = evento.target.closest('button');

                if (!botaoExcluir) {
                    return;
                }

                evento.preventDefault();
                excluirFornecedor(celula.getRow());
            },
            bottomCalc: rotuloRodape('Ações'),
        },
    ];
}

function inicializarBuscaGlobal(table) {
    const campo = document.getElementById('busca-global');
    let temporizador = null;

    campo.addEventListener('input', () => {
        clearTimeout(temporizador);

        temporizador = setTimeout(() => {
            const termo = campo.value.trim();

            table.setFilter(termo === '' ? [] : [{ field: 'busca_global', type: 'like', value: termo }]);
        }, 300);
    });
}

function montarQueryStringExportacao(table) {
    const parametros = new URLSearchParams();
    const termo = document.getElementById('busca-global').value.trim();

    if (termo !== '') {
        parametros.set('filter[0][field]', 'busca_global');
        parametros.set('filter[0][type]', 'like');
        parametros.set('filter[0][value]', termo);
    }

    table.getSorters().forEach((ordenacao, indice) => {
        parametros.set(`sort[${indice}][field]`, ordenacao.field);
        parametros.set(`sort[${indice}][dir]`, ordenacao.dir);
    });

    return parametros.toString();
}

function inicializarExportacoes(table) {
    const botoes = [document.getElementById('exportar-excel'), document.getElementById('exportar-pdf')];

    botoes.forEach((botao) => {
        botao.addEventListener('click', () => {
            const query = montarQueryStringExportacao(table);

            window.location.href = query === '' ? botao.dataset.url : `${botao.dataset.url}?${query}`;
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const elemento = document.getElementById('fornecedores-table');

    if (!elemento) {
        return;
    }

    const table = new Tabulator(elemento, {
        ajaxURL: elemento.dataset.url,
        layout: 'fitColumns',
        pagination: true,
        paginationMode: 'remote',
        sortMode: 'remote',
        filterMode: 'remote',
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50, 100],
        paginationCounter: 'rows',
        locale: 'pt-br',
        langs: { 'pt-br': langPtBr },
        placeholder: 'Nenhum fornecedor encontrado.',
        columns: criarColunas(),
    });

    inicializarBuscaGlobal(table);
    inicializarExportacoes(table);
});

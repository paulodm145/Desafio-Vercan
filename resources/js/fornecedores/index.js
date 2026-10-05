import 'quill/dist/quill.snow.css';

import { aplicarMascaraCep, aplicarMascaraDocumento } from './mascaras';
import { inicializarVisibilidade } from './visibilidade';
import { inicializarContatos } from './contatos';
import {
    inicializarIntegracaoCep,
    inicializarIntegracaoEstadoCidade,
    inicializarValidacaoEIntegracaoDoDocumento,
} from './integracao';
import { inicializarEditorObservacoes } from './editor';

function lerDadosIniciais() {
    const script = document.getElementById('dados-fornecedor-form');

    return script ? JSON.parse(script.textContent) : {};
}

document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.getElementById('form-fornecedor');

    if (!formulario) {
        return;
    }

    const dadosIniciais = lerDadosIniciais();

    aplicarMascaraDocumento(document.getElementById('cnpj_cpf'));
    aplicarMascaraCep(document.getElementById('cep'));

    inicializarVisibilidade();
    inicializarContatos(dadosIniciais);
    inicializarIntegracaoCep();
    inicializarIntegracaoEstadoCidade(dadosIniciais);
    inicializarValidacaoEIntegracaoDoDocumento(dadosIniciais.fornecedorId);
    inicializarEditorObservacoes();
});

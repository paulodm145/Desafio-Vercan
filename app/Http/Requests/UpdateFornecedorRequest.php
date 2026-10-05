<?php

namespace App\Http\Requests;

/**
 * Sem regras próprias de propósito: os campos e condições de obrigatoriedade
 * são idênticos entre criar e editar um fornecedor — a única diferença real
 * (`cnpj_cpf` não pode colidir com outro fornecedor, mas pode repetir o
 * próprio) já é tratada pelo `Rule::unique(...)->ignore($this->route('fornecedor'))`
 * dentro de `StoreFornecedorRequest::rules()`, que resolve para `null` em
 * `criar` e para o fornecedor da rota em `editar`.
 */
class UpdateFornecedorRequest extends StoreFornecedorRequest {}

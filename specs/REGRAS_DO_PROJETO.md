# Regras e convenções do projeto

Este documento registra regras obrigatórias para qualquer código novo ou alterado no Vercan. Em caso de dúvida,
preserve o padrão existente e consulte também [`../CLAUDE.md`](../CLAUDE.md) e a documentação técnica em `docs/`.

## Identificadores: ASCII puro

Todo identificador de código deve conter apenas caracteres ASCII (`U+0000` a `U+007F`). Esta regra é absoluta:

- Aplica-se a nomes de variáveis, constantes, funções, métodos, parâmetros, propriedades, classes, traits, enums,
  cases, namespaces, chaves de objetos, módulos e exports em PHP e JavaScript.
- O vocabulário de domínio continua em português, mas identificadores não usam acentos ou outros diacríticos:
  `ehMaisRecente`, `situacaoCnpj`, `razaoSocial`.
- Textos visíveis ao usuário, valores de domínio, mensagens, comentários e documentação podem e devem usar a
  ortografia correta em português. A restrição é para identificadores, não para conteúdo textual.
- Antes de finalizar uma alteração, procure caracteres não ASCII em identificadores de todos os arquivos de
  código alterados. Uma ocorrência como `éOMaisRecente` deve ser renomeada e todas as referências atualizadas.

## Idioma e nomenclatura

- Use português nos identificadores que representam conceitos e dados do domínio da aplicação.
- Escreva esses identificadores sem acentos, seguindo a regra ASCII acima; use camelCase em funções, métodos,
  propriedades e variáveis JavaScript/PHP conforme o padrão da linguagem e dos arquivos vizinhos.
- Preserve os nomes convencionais exigidos pelo framework e pelas bibliotecas, como `index`, `store`, `handle`,
  `boot`, `rules` e `toArray`.
- Use nomes claros e consistentes com a responsabilidade do símbolo. Não sacrifique a legibilidade para remover
  acentos: substitua-os pela letra ASCII equivalente (`é` → `e`, `ç` → `c`, `ã` → `a`).

## Frontend e JavaScript

- As páginas são renderizadas pelo Blade; o projeto não é uma SPA.
- Mantenha CSS e JavaScript no pipeline de assets do Vite e siga os entrypoints por funcionalidade registrados em
  `vite.config.js`. Não adicione dependências de CDN para os assets já empacotados pelo projeto.
- Divida comportamentos por módulos com responsabilidades claras e siga os padrões dos módulos vizinhos em
  `resources/js/`.
- O JavaScript controla interação e apresentação. Validação de segurança e regras de negócio permanecem no
  backend.
- Todo identificador JavaScript está sujeito à regra ASCII puro, inclusive callbacks e funções anônimas nomeadas.

## Backend e segurança

- Preserve as responsabilidades em camadas Controller → FormRequest → Service → Repository documentadas no
  `CLAUDE.md` e em `docs/ARQUITETURA.md`.
- Trate dados recebidos do cliente como não confiáveis. Mantenha validação e autorização no servidor e use escape
  ou sanitização apropriados ao contexto de saída.
- Use allowlists quando valores enviados pelo cliente puderem influenciar nomes de colunas, ordenação ou outras
  partes estruturais de uma consulta.
- Mantenha as integrações externas isoladas nos serviços existentes e diferencie indisponibilidade de serviço de
  ausência do recurso solicitado.

## Alterações e verificação

- Leia as instruções e os arquivos vizinhos antes de alterar código; prefira a menor mudança coesa que preserve a
  arquitetura atual.
- Atualize as referências ao renomear qualquer identificador e procure usos restantes do nome antigo.
- Quando uma alteração mudar comportamento, atualize ou acrescente testes que cubram o comportamento relevante,
  seguindo a política de testes do `CLAUDE.md` e de `docs/TESTES.md`.
- Não inclua segredos, arquivos `.env` locais ou artefatos gerados no controle de versão.

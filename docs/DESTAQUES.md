# Pontos de destaque

## Suporte ao CNPJ alfanumérico

Em 2026 a Receita Federal passou a emitir CNPJ no formato alfanumérico (Nota Técnica COFIS/RFB) — os 12 primeiros
caracteres podem ser dígitos **ou letras** (`0-9A-Z`), só os 2 dígitos verificadores finais continuam numéricos.
A validação do projeto (`App\Rules\CnpjValido`) implementa isso do zero:

- Regex de formato: `^[0-9A-Z]{12}\d{2}$`.
- O checksum mod-11 usa `ord(caractere) - 48` em vez do dígito literal — é assim que a nota técnica define o
  cálculo para caracteres alfabéticos, e a fórmula continua correta para CNPJs numéricos antigos como caso
  especial (sem precisar de dois algoritmos separados).
- Guarda explícita contra documento com todos os caracteres iguais (`00000000000000` fecha o checksum "por
  acidente" — passaria sem essa checagem).
- Validada contra vetores de teste **reais/oficiais** da própria nota técnica, não contra dígitos recalculados
  pela função sendo testada.

`App\Rules\CpfValido` é uma classe separada (não uma regra só ramificando por tamanho) por decisão deliberada de
manter cada documento com sua própria lógica, legível isoladamente.

## Exportação PDF/Excel com proteção contra Formula Injection

Os botões de exportar na listagem respeitam exatamente o filtro de busca em tela (mesma query da grid, sem
paginação). O export Excel implementa `WithCustomValueBinder`: qualquer campo de texto livre que comece com
`=`, `+`, `-` ou `@` — `razão social`, por exemplo — é gravado como string literal, nunca como fórmula. Sem essa
proteção, um valor como `=HYPERLINK("http://evil.com","clique")` cadastrado de propósito viraria uma fórmula
executável ao abrir a planilha no Excel de quem exportou.

## Integrações externas com tratamento de erro padronizado

ViaCEP (endereço por CEP), ReceitaWS (dados cadastrais por CNPJ) e Brasil API (carga inicial de estados/cidades)
são proxyadas pelo backend, nunca chamadas direto do navegador. As três compartilham a mesma exceção custom
(`ServicoExternoIndisponivelException`) quando a API externa está fora do ar — distinta de uma consulta que
simplesmente não encontrou nada, que responde `404` em vez de `503`. O frontend reage a cada caso de forma
diferente: "não encontrado" mostra uma mensagem específica; "serviço indisponível" mostra "tente novamente";
nenhuma falha de rede trava o resto do formulário em silêncio.

## `situacao_cnpj`: um campo que o usuário nunca escreve

O campo "Situação CNPJ" é `readonly` na UI, preenchido automaticamente pela consulta à ReceitaWS — mas isso por
si só não impede um POST direto (curl/Postman) setando qualquer valor arbitrário ali. A defesa real é no backend:
o valor só é persistido se tiver vindo de uma consulta real à ReceitaWS **nesta sessão**, pra este CNPJ
específico; qualquer outra coisa é ignorada, mantendo o valor já salvo (edição) ou `null` (criação).

## Grid server-side com proteção contra SQL injection

A listagem usa **Tabulator** com busca, ordenação e paginação 100% server-side. Ordenação é uma **allowlist**
explícita de colunas (`FornecedorRepository::COLUNAS_ORDENAVEIS`), não um passthrough do campo enviado pelo
cliente — duas colunas da grid são expressões `COALESCE(...)`, nem existem como nome de coluna real. Um campo de
ordenação em formato de SQL injection (`id); drop table fornecedores;--`) é silenciosamente ignorado, coberto por
teste específico.

A busca global também reconhece "Ativo"/"Inativo" como filtro de status (não só texto nas colunas), usando
prefixo em vez de substring — "Inativo" contém "ativo" como substring, então um `contains` ingênuo faria o filtro
"Ativo" trazer os dois status misturados.

## Autenticação com rate limiting preciso

Login usa `RateLimiter` manual, não o middleware `throttle:` de rota — a diferença importa: `throttle:` conta
**toda** requisição (inclusive logins certos), travando um usuário legítimo que loga várias vezes seguidas. Aqui
só tentativas **falhas** contam, chave `e-mail (case-insensitive) + IP`, e um login bem-sucedido zera o contador
imediatamente.

## XSS armazenado fechado em múltiplas camadas

- Campos de texto livre (nome de contato, e-mail, razão social) são sempre atribuídos via `.value`/`textContent`
  no JS, nunca interpolados direto em `innerHTML`.
- O editor de observações (Quill) tem o HTML sanitizado no **backend** antes de persistir — `mews/purifier` com
  allowlist restrita exatamente às tags que a toolbar do editor produz.
- Um helper de escape compartilhado (`escape-html.js`) cobre qualquer outro ponto que precise montar HTML a
  partir de dado vindo do servidor.

## Sidebar colapsável com persistência por usuário

Modo "ícone-apenas" real (não um "sumiço" completo da sidebar) via `sidebar-mini` do AdminLTE — preferência
guardada em `localStorage`, sobrevive à navegação entre páginas (cada link é um full page reload nesta aplicação
multi-página, não uma SPA).

## Páginas de erro customizadas

404, 403, 419 (sessão expirada), 429, 500 e 503 adaptadas do próprio AdminLTE, sem dependência de CDN e sem o
chrome autenticado — uma página de erro precisa continuar funcionando mesmo que o resto da aplicação esteja
quebrado.

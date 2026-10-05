# Arquitetura

Resumo das decisões estruturais do projeto. O detalhamento completo (com trechos de código e o "porquê" de cada
gotcha descoberto no caminho) vive em [`CLAUDE.md`](../CLAUDE.md) — este arquivo é a versão enxuta, pensada pra
leitura humana.

## Camadas: Controller → FormRequest → Service → Repository

Separação de responsabilidades estrita, sem atalho nem para algo que pareça trivial:

| Camada | Responsabilidade | Nunca faz |
|---|---|---|
| **Controller** | Recebe a request, chama um método de Service, devolve a response/view | Lógica de negócio, validação inline, query direta |
| **FormRequest** | Validação (uma classe por ação) | — |
| **Service** | Toda a lógica de negócio e orquestração | Instanciar outro Service/Repository com `new` |
| **Repository** | Único lugar onde Eloquent/query builder é usado | Lógica de negócio |

Injeção de dependência é sempre por **classe concreta** no construtor (sem interface por repository/service "só
por garantia" — isso vira burocracia sem ganho real num projeto deste tamanho; uma interface só entra quando há
mais de uma implementação de verdade).

## Integrações externas isoladas

Todo código que chama uma API de terceiros em tempo de request mora em `app/Services/Integracoes/` e
`app/Http/Controllers/Integracoes/`:

- **Brasil API** — estados/municípios, usada uma vez no seeder de localidades.
- **ViaCEP** — autopreenchimento de endereço a partir do CEP.
- **ReceitaWS** — autopreenchimento de dados cadastrais a partir do CNPJ.

As três passam por `ServicoExternoIndisponivelException`, uma exceção custom que padroniza "a API de terceiros
falhou" — capturada **uma única vez**, globalmente, em `bootstrap/app.php`, e traduzida pra `HTTP 503`. Uma
consulta que simplesmente não encontra nada (CEP/CNPJ inexistente) é uma resposta `404` diferente — nunca
confundida com a API estar fora do ar.

## Pt-BR no domínio, inglês no framework

Tabelas, colunas e variáveis que carregam dado de negócio são em português (`fornecedores`, `razao_social`,
`cnpj_cpf`). Nomes de classe, namespace e métodos do próprio Laravel (`index`, `store`, `handle`) continuam em
inglês — é o vocabulário de domínio que precisa ser consistente, não o esqueleto do framework.

## Segurança

- **Rate limiting no login** — manual (`RateLimiter::hit`/`tooManyAttempts`), não o middleware `throttle:` de
  rota: só conta tentativas **falhas**, e um login certo zera o contador — `throttle:` contaria toda requisição,
  travando até quem acerta a senha de primeira depois de logar várias vezes seguidas.
- **Sanitização de HTML armazenado** — o editor de observações (Quill) tem o HTML sanitizado no **backend**
  (`mews/purifier`, allowlist restrita às tags que a toolbar realmente produz) antes de persistir. Validação só
  no client nunca é suficiente — dá pra contornar com um POST direto.
- **Escape consistente em todo ponto que monta HTML via JS** — nenhum dado vindo do servidor é interpolado direto
  em `innerHTML` sem passar por `.value`/`textContent` ou por um helper de escape dedicado.
- **Formula/CSV Injection bloqueada no export Excel** — um valor como `=HYPERLINK(...)` em qualquer campo de
  texto livre é gravado como string literal, nunca como fórmula executável.
- **Headers de segurança** (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`) em toda resposta.
- Mais detalhes e o porquê de cada uma em [`DESTAQUES.md`](./DESTAQUES.md).

## Frontend

Blade renderizado no servidor + **AdminLTE v4** (Bootstrap 5) como kit de UI — sem Tailwind, sem dependência via
CDN (tudo empacotado pelo Vite). Cada página com biblioteca própria (Tabulator, SweetAlert2, IMask, Quill) tem seu
próprio entry point no Vite, carregado só onde é usado — outras páginas não pagam o custo.

## Páginas de erro

`resources/views/errors/{403,404,419,429,500,503}.blade.php` — adaptadas do próprio AdminLTE, mas sem CDN e
deliberadamente sem o chrome autenticado (navbar/sidebar fariam suas próprias queries, arriscando uma segunda
falha em cima da primeira).

# CLAUDE.md

Este arquivo fornece orientações ao Claude Code (claude.ai/code) ao trabalhar com código neste repositório.

## Projeto

Aplicação Laravel 13 (PHP 8.4) rodando em Docker: PHP-FPM + Nginx + Postgres 17. Todo trabalho na aplicação
acontece dentro do container `app` — não há instalação local de PHP/Composer no host, apenas Node para o
pipeline de assets do Vite.

O frontend é Blade renderizado no servidor usando **AdminLTE v4** (Bootstrap 5) como kit de UI, não Tailwind —
o Tailwind foi removido do skeleton padrão do Laravel porque seu reset conflita com o reboot do Bootstrap
embutido no `adminlte.css`. Bootstrap e Bootstrap Icons são instalados via npm e empacotados pelo Vite; não há
dependências via CDN para CSS/JS.

## Comandos

Todos os comandos PHP/artisan rodam dentro do container `app`.

```sh
docker compose up -d                 # sobe a stack (os containers persistem entre stop/down; os dados do db ficam em um volume nomeado)
docker compose stop                  # para os containers, mantém tudo
docker compose down                  # remove containers + rede, o volume (dados do db) sobrevive
docker compose exec app bash         # abre um shell no container PHP
docker compose logs -f app|nginx|db  # acompanha os logs de um serviço

docker compose exec app php artisan test                      # suíte de testes completa
docker compose exec app php artisan test --filter=TestName    # um teste específico
docker compose exec app php artisan test tests/Feature/Xyz.php

docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker

docker compose exec app vendor/bin/pint        # padronização de código (Laravel Pint) — rodar antes de commitar mudanças PHP
```

Os assets do frontend são buildados no **host**, não no container (o Node não está instalado na imagem `app`):

```sh
npm install
npm run dev      # servidor Vite em modo dev (HMR)
npm run build    # build de produção -> public/build/ (manifest.json), necessário para o @vite() resolver sem o servidor de dev rodando
```

Os testes rodam contra **sqlite `:memory:`** (configurado em `phpunit.xml`), totalmente independente do
container Postgres — não é preciso que o `db` esteja no ar para rodar a suíte de testes.

## Arquitetura

### Arquitetura em camadas: Controller → FormRequest → Service → Repository

Esta codebase impõe uma separação de responsabilidades estrita. Ao implementar qualquer funcionalidade, siga
essas camadas — não pule etapas mesmo para algo que pareça trivial:

- **Controllers** (`app/Http/Controllers`) apenas recebem a request e retornam uma response. Nenhuma lógica de
  negócio, nenhuma validação, nenhuma consulta direta ao banco/Eloquent. O corpo de um método de controller deve
  ser essencialmente: chamar o `validated()` de um `FormRequest`, chamar um método de Service, retornar uma
  response/view.
- **Validação** vive em classes `FormRequest` dedicadas (`app/Http/Requests`), uma por ação (ex.:
  `StoreFornecedorRequest`, `UpdateFornecedorRequest`) — nunca `$request->validate(...)` inline no controller.
  Tipe o `FormRequest` na assinatura do método do controller para que o Laravel valide antes do corpo do método
  rodar.
- **Services** (`app/Services`) contêm toda a lógica de negócio e orquestração. Controllers dependem de services
  via injeção no construtor, nunca instanciando com `new`.
- **Repositories** (`app/Repositories`) são o *único* lugar onde consultas ao banco acontecem (Eloquent ou query
  builder). Services chamam métodos de repository; eles nunca consultam models diretamente.
- Um repository recebe seu model via injeção no construtor (`public function __construct(private readonly
  Fornecedor $model) {}`) — nunca uma chamada estática `Fornecedor::query()`/`Fornecedor::where(...)` dentro de um
  método do repository. O container do Laravel resolve um Eloquent model puro sem precisar de argumentos, então
  isso não requer nenhum binding explícito em lugar nenhum.
  - Para uma consulta encadeada única, chame direto em `$this->model` (`$this->model->where(...)->first()`) — sem
    precisar de `->newQuery()`. `Model::__call()` já repassa qualquer método não definido para `$this->newQuery()`
    internamente (veja `vendor/laravel/framework/.../Eloquent/Model.php`), então `$this->model->where(...)` e
    `$this->model->newQuery()->where(...)` executam exatamente o mesmo código; a chamada explícita é só ruído.
  - A única vez em que `->newQuery()` *é* necessário: quando um `Builder` precisa ser construído ao longo de mais
    de uma chamada de método (ex.: `FornecedorRepository::paginar()` passa a mesma query para os helpers separados
    `aplicarBusca()`/`aplicarOrdenacao()`). `$this->model` é um `Fornecedor`, não um `Builder` — atribuí-lo
    diretamente a uma variável e passá-la para um método tipado `Builder $consulta` falha na checagem de tipos.
    Chame `$this->model->newQuery()` uma vez ali para obter um `Builder` de verdade para ir acumulando condições.
- A injeção de dependência é feita tipando a classe **concreta** do Service/Repository no construtor — o
  container do Laravel resolve automaticamente. Não introduza uma interface por repository/service "só por
  garantia"; isso é burocracia desnecessária aqui. Só adicione uma interface quando houver um motivo real para
  isso (ex.: mais de uma implementação real), não como padrão default para toda classe.

### Convenções de nomenclatura e tipagem

- **A nomenclatura de domínio é em pt-BR**: tabelas/colunas do banco, nomes de variáveis de model/domínio e nomes
  de campos de FormRequest são sempre em português (ex.: tabela `fornecedores`, colunas `razao_social`, `cnpj`,
  `nome_fantasia`). Nomes de nível de framework (classes, namespaces, métodos como `index`/`store`/`handle`)
  continuam em inglês/convenção do Laravel como de costume — é o vocabulário de *domínio* (campos, variáveis que
  carregam dados de domínio) que precisa ser pt-BR, de forma consistente em migrations, models, requests, services
  e views.
- **Tipe tudo o máximo possível**: parâmetros de método, tipos de retorno e propriedades devem ser todos tipados
  (o PHP 8.4 suporta isso plenamente — use tipos union/nullable, propriedades tipadas, tipos de retorno
  `void`/`self`/enum conforme apropriado). Evite assinaturas com `mixed` implícito por omissão de tipo.
- **Sempre defina `protected $table` explicitamente em todo model.** O adivinhador de nome de tabela do Eloquent
  pluraliza com regras do inglês, o que quebra silenciosamente em palavras pt-BR (`Fornecedor` → adivinha
  `fornecedors`, não `fornecedores`). Não confie que vai coincidir por acaso — funciona para algumas palavras
  (`Estado`→`estados`) e não para outras, o que é uma armadilha em si.

### Comentários

Evite comentários por padrão. Só escreva um quando for genuinamente útil ou estritamente necessário — um "porquê"
não óbvio (uma restrição oculta, um workaround, um invariante sutil), nunca uma reafirmação do que o código já
diz. Se remover o comentário não tornaria o código mais difícil de entender, não o escreva.

### Política de testes

Não escreva um teste para tudo — apenas para cenários que pegariam um bug real e de alto impacto se quebrassem
(comportamento de auth/sessão, constraints de banco, casos de borda de regras de negócio). Pule testes para coisas
que o próprio Laravel já garante (roteamento do framework, regras de validação funcionando, uma view renderizando
sem lógica) e pule testes para código trivial sem ramificação. Tudo bem verificar comportamento de forma ad hoc
enquanto constrói uma funcionalidade (curl, tinker, execuções avulsas de `php artisan test`) sem transformar cada
uma dessas checagens em um teste commitado — mantenha a suíte permanente enxuta, não um registro de tudo que foi
checado manualmente pelo caminho.

**Teste verde não é sinônimo de código correto** (ver
[paulorb.dev/blog/tdd-na-era-da-ia](https://paulorb.dev/blog/tdd-na-era-da-ia-quando-teste-verde-deixa-de-significar-codigo-correto)).
Regras específicas, inclusive — e principalmente — para quando quem está escrevendo o teste é um agente de IA:

- **Nunca apague, pule (`markTestSkipped`) ou comente um teste só para a suíte passar.** Um teste falhando é um
  requisito não atendido, não um obstáculo — a ação correta é corrigir o código (ou, se o teste é que está errado,
  corrigir o teste com justificativa clara, nunca silenciá-lo). O mesmo vale ao contrário: não escreva um teste só
  para "ter mais cobertura" — cada teste precisa proteger um comportamento real.
- **O valor esperado é sempre um literal da regra de negócio, nunca recalculado a partir de uma constante ou
  método da própria classe testada.** `$this->assertSame(90.0, $service->calcular(100.0, 'VIP'))` protege; `$esperado
  = 100.0 * (1 - DescontoService::TAXA_VIP); $this->assertSame($esperado, ...)` é um "teste espelho" — se a
  constante estiver errada, o teste aprova o mesmo valor errado. Nas regras de CPF/CNPJ deste projeto, por
  exemplo, os vetores de teste são documentos reais/oficiais (Nota Técnica COFIS/RFB), nunca dígitos verificadores
  calculados pelo próprio `CnpjValido` sendo testado.
- **Asserção tem que ser específica o bastante pra quebrar se o comportamento quebrar.** `assertNotNull`/`assertTrue(true)`
  isolados não protegem nada — só valem como checagem de pré-condição antes de uma asserção forte que vem a
  seguir (ex.: confirmar que o registro foi criado antes de checar seus relacionamentos), nunca como a única
  asserção do teste.
- **Teste comportamento observável, não implementação.** Não mocka o objeto sendo testado (isso testa o mock, não
  o código) e não deve quebrar com uma refatoração válida que preserva o comportamento.
- **Casos de borda da mesma regra agrupados, com valores no limite** (um abaixo, exatamente no limite, um acima) —
  como os pares `exige_inscricao_estadual_quando_...`/`nao_exige_inscricao_estadual_quando_...` em
  `FornecedorTest`, não um teste solto genérico "válido" e outro "inválido" sem relação clara entre si.

### Integridade do banco de dados

Toda migration precisa proteger a integridade referencial, não só criar colunas:

- Chaves estrangeiras usam `->foreignId(...)->constrained(...)` (ou equivalente) com um
  `->cascadeOnDelete()`/`->nullOnDelete()`/`->restrictOnDelete()` explícito — nunca uma coluna FK sem constraint.
- Adicione índices para chaves estrangeiras e para colunas que serão filtradas/ordenadas/usadas em join — não
  confie só no Eloquent para deixar as buscas rápidas. **O Postgres não cria índice automático para coluna de
  FK** (diferente do MySQL/InnoDB, que cria implicitamente ao adicionar a constraint) — `->foreignId(...)->constrained(...)`
  sozinho não basta aqui, sempre complemente com `$table->index('coluna')` explícito. Essa lacuna já aconteceu de
  verdade nas migrations de `fornecedores`/`fornecedor_contatos`/`fornecedor_telefones`/`fornecedor_emails`
  (corrigida depois via code review) — `cidades.estado_id` é o exemplo de referência que sempre teve isso certo.
- Prefira constraints a nível de banco (unique, not-null, FK) em vez de validar unicidade/existência só na
  aplicação.
- **Uma coluna que armazena um enum PHP backed (qualquer semântica "tipo"/"indicador"/"regime" — um conjunto
  fechado e fixo de valores string) é `$table->enum('col', [...])` na migration, nunca um `string()` simples.**
  Os valores permitidos ficam hardcoded na própria migration (não lidos da classe `App\Enums\*`) — migrations são
  um snapshot do schema em um ponto no tempo, elas não devem acessar código da aplicação que pode mudar depois.
  Isso funciona entre bancos diferentes (o Laravel emite uma constraint `CHECK` no Postgres, um `ENUM` nativo no
  MySQL, um `CHECK` no sqlite também), então funciona igual na suíte de testes e no Postgres. Quando um enum ganha
  um novo case, adicione-o tanto na classe `App\Enums` quanto no array de valores permitidos da migration, na
  mesma mudança.

### Docker / variáveis de ambiente

O `docker-compose.yml` separa intencionalmente a porta **interna** do Postgres da porta **exposta no host** para
não quebrar a conexão do Laravel com o banco:

- `DB_PORT` (no `.env`) é sempre `5432` — a porta que o container `app` usa para alcançar o `db` pela rede interna
  do Docker (`DB_HOST=db`). Essa é também a variável `DB_PORT` do próprio Laravel — não reaproveite para
  mapeamento de host.
- `FORWARD_APP_PORT` / `FORWARD_DB_PORT` são as portas expostas no host (padrão `8000` e `5434` — `5434` porque
  `5432`/`5433` já estavam em uso na máquina de desenvolvimento; ajuste por máquina, não por lógica da aplicação).

O `docker/php/Dockerfile` constrói o PHP 8.4-FPM com as extensões que Laravel + Postgres precisam (`pdo_pgsql`,
`intl`, `gd`, `bcmath`, `zip`, `opcache`, …) e cria um usuário `www` cujo UID/GID batem com o usuário do host (via
argumentos de build `UID`/`GID`, padrão 1000), para que arquivos criados dentro do container não fiquem com dono
root no host. `docker/nginx/` contém o vhost (`conf.d/app.conf`) apontando para `/var/www/html/public` e fazendo
proxy do PHP para `app:9000`.

**Hardening básico de infra**:

- `docker/nginx/conf.d/app.conf`'s bloco `location ~ \.php$` tem `try_files $uri =404;` antes do `fastcgi_pass`
  — sem isso (o padrão de qualquer config nginx+PHP-FPM escrita à mão, diferente do scaffolding do
  Forge/Laravel), qualquer URL terminando em `.php` era repassada ao PHP-FPM mesmo que o arquivo não existisse no
  disco, retornando "No input file specified" em vez de um 404 limpo.
- `docker/php/php.ini` tem `expose_php = Off` — sem isso, toda resposta vazava `X-Powered-By: PHP/8.4.x`,
  fingerprinting trivial da stack. **Esse arquivo é `COPY`-ado na imagem em build time (não é bind mount como o
  Nginx)** — uma mudança nele exige `docker compose build app` + recriar o container, não só editar o arquivo.
- `App\Http\Middleware\SecurityHeaders` (registrado em `bootstrap/app.php` via `$middleware->web(append: [...])`)
  adiciona `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff` e `Referrer-Policy:
  strict-origin-when-cross-origin` em toda resposta do grupo `web`. Uma Content-Security-Policy completa foi
  deliberadamente deixada de fora: o script inline de resolução de tema em `components/layout.blade.php` (roda
  antes do primeiro paint pra evitar flash do tema errado) exigiria nonces em todo Blade ou `unsafe-inline`, o
  que anularia boa parte do propósito de uma CSP — os três headers acima não têm esse custo.

### Pipeline de assets do frontend

- `resources/css/vendor/adminlte.css` e `resources/js/vendor/adminlte.js` são os arquivos-fonte do AdminLTE v4,
  copiados verbatim (não é uma dependência gerenciada — não existe pacote npm para esse build do AdminLTE). Não
  edite esses arquivos manualmente; recopie a partir da fonte do AdminLTE + `sed '/sourceMappingURL/d'` caso
  precisem ser atualizados.
- `resources/css/app.css` / `resources/js/app.js` são os entrypoints reais do Vite: eles fazem `@import`/`import`
  dos arquivos vendored do AdminLTE mais `bootstrap` e `bootstrap-icons` do `node_modules`. Adicione qualquer nova
  dependência de frontend via npm + um import aqui, não uma tag `<script>`/`<link>` de CDN no Blade.
- `adminlte.js` é o build ESM; ele se auto-inicializa ao ser importado (registra seu próprio listener de
  `DOMContentLoaded`), então importá-lo em `app.js` já é suficiente — não é preciso chamar `AdminLTE.init()`
  manualmente.
- Uma página com seu próprio JS/CSS pesado (biblioteca de máscara, editor rich-text, …) ganha seu **próprio
  entrypoint do Vite** — veja `resources/js/fornecedores/` — registrado no array `input` do `vite.config.js` e
  carregado só naquela view via `@push('scripts') @vite([...]) @endpush` contra o
  `@stack('scripts')`/`@stack('styles')` do `<x-layout>`. Não adicione dependências específicas de página ao
  `app.js`/`app.css` global — outras páginas pagariam o custo também.
- **`build.cssMinify` é `false`.** O minificador de CSS padrão do Vite 8 (nesse build baseado em Rolldown) esvazia
  silenciosamente o conteúdo de glyph escapado do `bootstrap-icons` (`content: "\f64d"` → `content: ""`), então
  todo ícone em toda página para de renderizar silenciosamente num build de produção, enquanto fica
  completamente normal no `npm run dev`. O `esbuild` como minificador resolveria isso mas não vem junto com o
  Vite 8 por padrão e não é dependência do projeto de outra forma — adicioná-lo só para isso pareceu pior do que
  desabilitar a minificação, o que custa alguns KB de gzip, não correção. Se os ícones "sumirem" de novo só em
  build de produção, cheque isso primeiro antes de assumir que é um bug de Blade/markup.
- **A sidebar fica colapsada (modo ícone-apenas) por padrão ao abrir.** O `<body>` em `layout.blade.php` carrega
  as classes `sidebar-mini sidebar-collapse` desde o HTML inicial. Sem a classe `sidebar-mini`, o CSS do AdminLTE
  trata `sidebar-collapse` como "esconder completamente" (`margin-left: calc(var(--lte-sidebar-width) * -1)` —
  regra `.sidebar-collapse:not(.sidebar-mini) .app-sidebar`); só com as duas classes juntas o AdminLTE aplica o
  modo mini real (`.sidebar-mini.sidebar-collapse .app-sidebar { min-width/max-width: 4.6rem }`), que mantém os
  ícones visíveis e expande no hover. O `<aside>` em `sidebar.blade.php` também tem
  `data-enable-persistence="true"`: a classe `PushMenu` do `adminlte.js` lê esse atributo e, quando habilitado,
  grava o estado atual (colapsada/expandida) no `localStorage` — assim, se o usuário expandir manualmente, essa
  escolha sobrevive à navegação entre páginas (cada link é um full page reload, não SPA) em vez de voltar a
  colapsar a cada tela; sem persistência, o estado inicial do `<body>` venceria em toda navegação.
- **Marca abreviada ("VR") e botão "Sair" só com ícone no modo colapsado**: o AdminLTE não tem pronto nem um
  elemento de texto curto para a marca (`logo-xs`/`logo-xl` são para trocar *imagens*, com `position: absolute`),
  nem esconde texto de botões fora do `.sidebar-menu`. `resources/css/app.css` tem duas regras customizadas que
  replicam manualmente o mesmo padrão mostrar/esconder-no-hover que o AdminLTE já usa em `.brand-text` e nos `<p>`
  dos `nav-link`: `.brand-text-collapsed` (o `<span>VR</span>` em `sidebar.blade.php`, ao lado do `<span
  class="brand-text">`) e `.sidebar-footer-text` (o texto "Sair" dentro do botão de logout). Ambas ficam
  escondidas por padrão, aparecem só com `.sidebar-mini.sidebar-collapse`, e voltam a esconder no
  `:hover` do `.app-sidebar` (quando o texto completo "Vercan"/"Sair" reaparece) — mantendo o `adminlte.css`
  vendored intocado.
- **Nunca coloque `d-flex` diretamente em `.card-header`.** O CSS do AdminLTE dá ao `.card-header` (e também
  `.card-body`, `.card-footer`) um pseudo-elemento de clearfix (`::after { display: block; clear: both; content:
  "" }`) para seu layout original baseado em float de `.card-title`/`.card-tools`. Se o próprio header também
  virar um flex container, essa caixa `::after` gerada passa a ser um terceiro item flex de verdade — invisível,
  mas `justify-content: space-between` passa a dividir o espaço entre três itens em vez de dois, então um botão
  alinhado à direita acaba ficando mais ou menos no meio em vez de na borda. Coloque `d-flex
  justify-content-between` em uma `<div>` interna envolvida dentro do `.card-header` (simples, não-flex) em vez
  disso — veja `fornecedores/form.blade.php` e `fornecedores/index.blade.php`.

### Layout de componentes Blade

Dois layouts raiz, ambos **componentes** Blade (não layouts `@extends`/`@include`):

- `<x-layout title="...">` (`resources/views/components/layout.blade.php`) — shell completo da aplicação
  autenticada: renderiza `<x-navbar />`, `<x-sidebar />`, o `$slot` dentro de `.app-content`, e `<x-footer />`.
  Passe o título da página na prop `title`; ela é usada no `<title>` e no breadcrumb.
- `<x-guest-layout title="...">` — shell HTML simples sem navbar/sidebar, usado pela tela de login e pelas
  páginas de erro (ver "Páginas de erro" abaixo) — qualquer página que precise funcionar sem depender de sessão
  autenticada ou do estado do resto da aplicação.

`resources/views/components/sidebar.blade.php` é a fonte única de verdade para a navegação lateral. É mantida
deliberadamente curta (apenas rotas reais já existentes) — adicione um novo `<li>` ali quando uma nova seção de
nível superior for lançada; não pré-construa itens de navegação para páginas que ainda não existem. O estado
ativo é calculado via `request()->routeIs(...)`, nunca fixado manualmente por página.

`resources/views/components/navbar.blade.php` só tem chrome que funciona sem dados de backend (toggle da
sidebar, fullscreen, alternância de tema claro/escuro). Os dropdowns de mensagens/notificações/usuário do demo do
AdminLTE foram deliberadamente removidos — eles renderizam dados falsos — e só devem voltar quando houver um
usuário autenticado real / uma fonte de notificação real para alimentá-los.

### Páginas de erro

`resources/views/errors/{403,404,419,429,500,503}.blade.php` — Laravel resolve essas automaticamente pelo código
HTTP (convenção `errors.{código}` → `resources/views/errors/{código}.blade.php`, nenhum registro extra
necessário). Adaptadas de `pages/404.html`/`500.html` da pasta do AdminLTE que serve de base ao projeto, mas sem
os links de CDN do original (fontsource, overlayscrollbars, bootstrap-icons, bootstrap — tudo via CDN na
referência) — aqui usam `<x-guest-layout>` (CSS/JS já bundlados pelo Vite, zero CDN, consistente com o resto do
projeto) em vez de montar um `<head>` próprio.

- Deliberadamente **sem** o chrome autenticado (`<x-layout>`/navbar/sidebar): uma página de erro — sobretudo
  500/503 — precisa continuar renderizando mesmo que algo no resto da aplicação esteja quebrado; depender da
  sidebar/navbar (que fazem suas próprias queries/checks) arrisca uma segunda falha em cima da primeira.
- `<x-erro codigo="..." titulo="..." mensagem="..." cor="...">` (`resources/views/components/erro.blade.php`) é o
  componente compartilhado pelas 6 páginas — evita duplicar a mesma estrutura de card centralizado 6 vezes. A
  busca funcional que a página 404 original do AdminLTE tinha foi removida (não existe endpoint de busca global
  nesta aplicação — um formulário decorativo que não busca nada é pior que não ter formulário).
- Todas linkam só para `route('home')` como ação — funciona tanto autenticado (vai pro painel) quanto não
  (`redirectGuestsTo` manda pro login de qualquer forma), não precisa ramificar por estado de auth.

### Auth

Autenticação de sessão padrão do Laravel (facade `Auth`, guard `web`, `AuthController` + `AuthService`, sem
broker/recuperação de senha — isso é intencionalmente não implementado). Tudo em `routes/web.php` está envolvido
no middleware `guest` (`/login`) ou no middleware `auth` (todas as outras rotas) — qualquer rota nova precisa ser
adicionada dentro de um desses dois grupos, não há rota pensada para ser acessível sem autenticação além de
`/login`.

**Rate limiting manual no login** (`AuthService::login()`), não o middleware `throttle:` de rota: usa
`RateLimiter::tooManyAttempts()`/`hit()`/`clear()` diretamente, chave `email (lowercase) + IP`, 5 tentativas por
60 segundos. A diferença importa — `throttle:` conta *toda* requisição à rota (inclusive logins corretos,
travando um usuário legítimo que loga várias vezes seguidas); aqui só tentativas **falhas** incrementam o
contador, e um login bem-sucedido chama `RateLimiter::clear()` imediatamente. Ao estourar o limite, lança
`ValidationException::withMessages(['email' => trans('auth.throttle', [...])])` — isso reaproveita o mesmo fluxo
de redirect-back-com-erro que já existia para "credenciais inválidas" (a view só faz `$errors->first()`, sem
tratamento especial por chave), e `lang/pt_BR/auth.php`'s chave `throttle` (publicada desde o início via
`lang:publish`, nunca usada até então) finalmente tem um caller.

### Locale

`APP_LOCALE=pt_BR`, com `lang/pt_BR/*.php` contendo as strings traduzidas de validação/auth/paginação
(publicadas via `php artisan lang:publish`, depois traduzidas — o Laravel 11+ não vem com um diretório `lang/`
por padrão). `lang/en/passwords.php` foi deletado em vez de traduzido: não há funcionalidade de recuperação de
senha, então esse arquivo não tinha nenhum caminho de código que o lesse. `StoreFornecedorRequest` depende do
mapa `attributes` de `lang/pt_BR/validation.php` para nomes humanos de campo (`cnpj_cpf` → "CNPJ/CPF", etc.) em
vez de um override `attributes()` por request — mantenha os novos rótulos de campo lá, não duplicados na classe
do request.

### Localidades (estados/cidades) e APIs externas

Todo Service/Controller cujo trabalho é chamar uma API de terceiros vive dentro de uma subpasta `Integracoes` —
`app/Services/Integracoes/` (`BrasilApiService`, `ViaCepService`, `ReceitaWsService`, namespace
`App\Services\Integracoes`) e `app/Http/Controllers/Integracoes/` (`CepController`, `CnpjController`, namespace
`App\Http\Controllers\Integracoes`). Isso é especificamente para chamadas *ao vivo, em tempo de request* a uma
API externa — `CidadeController` fica na pasta `Controllers` normal mesmo que seus dados tenham vindo
originalmente da Brasil API, porque em tempo de request ele só lê nossa própria tabela `cidades`, não chama nada
externo. Ao adicionar uma nova integração externa, coloque-a em `Integracoes` desde o início em vez de mover
depois.

`database/seeders/LocalidadeSeeder.php` popula `estados` e `cidades` a partir da Brasil API (`BrasilApiService`)
— é um seeder, não um comando artisan, e `DatabaseSeeder` o chama, então um `php artisan migrate --seed` simples
já carrega esses dados antes de qualquer coisa que dependa dessas tabelas (principalmente os selects de UF/cidade
do formulário de Fornecedor). É idempotente (`updateOrCreate` em `codigo_ibge`), seguro para rodar de novo
sozinho também (`php artisan db:seed --class=LocalidadeSeeder`).

Três endpoints sob o middleware `auth` existem puramente como backends AJAX para o formulário de Fornecedor, cada
um fazendo proxy de uma API de terceiros no servidor em vez de chamar direto do navegador:

- `GET /estados/{estado}/cidades` — cidades de um estado, para o cascade UF→cidade. Não fica em `Integracoes`,
  ver acima.
- `GET /ceps/{cep}` (`ViaCepService`) — consulta ViaCEP. O ViaCEP retorna **HTTP 200 com `{"erro": true}`** para
  um CEP desconhecido, não um 404 — `ViaCepService::buscarPorCep()` checa isso explicitamente e retorna `null`. O
  controller também resolve o nome de UF/cidade retornado para nossas próprias linhas de `estados`/`cidades`
  (`CidadeRepository::buscarPorNomeEEstado()`) para que o frontend possa simplesmente selecionar valores de
  option já existentes.
- `GET /cnpjs/{cnpj}` (`ReceitaWsService`) — consulta ReceitaWS. Esse é proxyado por necessidade, não só por
  consistência: o endpoint gratuito da ReceitaWS não envia headers CORS, então uma chamada direta do navegador
  falha. O ViaCEP suporta CORS, mas é proxyado mesmo assim pelo mesmo motivo de simetria/cache/tratamento de
  erro.

**Status HTTP correto para cada desfecho, não só 200 com uma flag no corpo**: `/ceps/{cep}` e `/cnpjs/{cnpj}`
respondem `404` com `{"encontrado": false}` quando a consulta roda normalmente mas o documento não existe —
antes retornavam `200` para esse caso, escondendo o resultado dentro do corpo. Falha de **conexão** com a API
externa (ViaCEP/ReceitaWS fora do ar, timeout) é tratada à parte: `ViaCepService`/`ReceitaWsService` lançam
`App\Exceptions\ServicoExternoIndisponivelException` em vez de devolver `null` nesse caso — devolver `null` igual
ao "não encontrado" faria o controller responder `404`, dando a entender erroneamente que o CEP/CNPJ é inválido
quando na verdade a API nem respondeu. Essa exceção é capturada **uma vez só**, globalmente, em
`bootstrap/app.php` (`$exceptions->render(...)`) e vira `503` — qualquer integração futura em
`Integracoes` que lance essa mesma exceção já ganha o tratamento de graça, sem repetir try/catch em cada
controller. No frontend, `buscarJson()` (`resources/js/fornecedores/integracao.js`) trata `404` como resposta
válida pra fazer parse do corpo (é o formato deliberado desses dois endpoints), mas qualquer outro status de erro
(`503` incluso) cai no mesmo `ERRO_REDE` de uma falha de rede — o usuário só precisa saber que falhou e tentar de
novo, não qual status exato voltou. `carregarCidades()` tem uma guarda `Array.isArray()` extra porque
`/estados/{estado}/cidades` usa route-model-binding do Eloquent: um 404 ali (se o `estado_id` for inválido —
não deveria acontecer vindo do `<select>`, mas um POST manual poderia forçar) tem corpo `{"message": "..."}` do
próprio Laravel, não um array de cidades, e sem a guarda o `.map()` seguinte quebraria.

**`ServicoExternoIndisponivelException` é a exceção padrão para "uma API de terceiros falhou"** em todo
`app/Services/Integracoes/` — `BrasilApiService` (usado por `LocalidadeSeeder`) também a lança, tanto em falha de
conexão quanto em resposta HTTP de erro do `->throw()`, em vez de deixar vazar `ConnectionException`/
`RequestException` crus do cliente HTTP do Laravel. Ao contrário de ViaCEP/ReceitaWS, aqui não existe um "não
encontrado" legítimo pra distinguir (é sempre a lista fixa de estados/municípios do IBGE) — então qualquer falha,
de conexão ou de resposta, vira a mesma exceção. Como `BrasilApiService` só é chamado em contexto de CLI (seeder),
essa exceção nunca passa pelo `$exceptions->render()` de `bootstrap/app.php` — simplesmente propaga e quebra o
comando com stack trace, que é o comportamento esperado/correto pra uma seed falhando, não algo que precisa de
tratamento especial.

**Falha de rede no frontend não trava o resto do formulário em silêncio**: `resources/js/fornecedores/integracao.js`'s
`buscarJson()` envolve o `fetch()` num `try/catch` — uma falha de rede/DNS/CORS faz `fetch()` rejeitar a Promise
(diferente de uma resposta HTTP de erro, que só cai no `!resposta.ok`), e sem esse catch a exceção subia pelos
`await` de quem chama e abortava o resto do handler de `blur` do campo CNPJ/CPF: `verificarDocumentoDuplicado()`
falhando impedia `buscarReceitaWs()` de sequer rodar em seguida, sem nenhum feedback ao usuário. `buscarJson()`
retorna o sentinel `ERRO_REDE` (um `Symbol`, nunca confundível com um valor de API real) nesse caso; cada chamador
(`preencherEnderecoPorCep`, `buscarReceitaWs`, `verificarDocumentoDuplicado`, `carregarCidades`) checa esse
sentinel explicitamente e mostra "Não foi possível conectar ao servidor. Tente novamente." no mesmo elemento de
feedback que já existia para "não encontrado"/"duplicado" — `mostrarFeedback()` guarda o texto original escrito
no Blade em `data-mensagem-original` antes de sobrescrevê-lo, e o restaura da próxima vez que for exibido sem uma
mensagem customizada, pra um erro de rede não deixar o aviso com o texto errado permanentemente.

**Respostas fora de ordem não sobrescrevem o formulário**: se o usuário dispara duas buscas do mesmo tipo em
sequência rápida (ex.: digita um CEP, corrige, dá blur de novo), nada garante que as respostas HTTP voltem na
mesma ordem em que as requisições saíram — sem proteção, a resposta que *chega* por último vence, não a que foi
*disparada* por último, podendo preencher o formulário com dados de um CEP/CNPJ diferente do que está no campo
naquele momento. `criarGuardaDeCorrida()` cria um contador incremental simples (sem `AbortController` — não
cancela a requisição em voo, só impede que o resultado dela seja aplicado se já estiver obsoleto); cada fluxo
tem sua própria instância independente (`guardaEndereco`, `guardaCidades`, `guardaDocumento`) porque escrevem em
conjuntos de campos diferentes. Um token é reivindicado **antes de qualquer `await`** — inclusive nos retornos
antecipados dos handlers de blur (campo vazio, documento com formato inválido) — porque mesmo esses caminhos
síncronos precisam contar como "a interação mais recente" para que uma busca anterior, ainda em voo, se reconheça
como obsoleta quando finalmente responder.

### Fornecedores

O formulário de criação/edição (`resources/views/fornecedores/form.blade.php`) e a listagem/grid (`index`) estão
ambos implementados — veja "Fornecedores grid (tela de listagem)" e "Fornecedores export (PDF/Excel)" abaixo
para o segundo.

- **Modelo de contato**: "Contato Principal" e "Contatos Adicionais" são a mesma entidade subjacente
  (`fornecedor_contatos`, `FornecedorContato`), distinguidos apenas por um booleano `principal`. Todo fornecedor
  ganha exatamente um contato `principal` (criado mesmo que o formulário não tenha campos de
  nome/empresa/cargo para ele — esses ficam nulos); todo outro bloco de contato enviado é uma linha não
  principal. Ambos compartilham as mesmas tabelas filhas `fornecedor_telefones` / `fornecedor_emails`. Não
  introduza um conjunto paralelo de tabelas para o contato principal — isso foi uma deduplicação deliberada, não
  um acidente.
- **Atualização é substituir-tudo, não diff**: `FornecedorService::atualizar()` apaga todos os `contatos` do
  fornecedor (em cascata para seus telefones/emails) e os recria a partir do formulário enviado. Não há
  rastreamento no cliente de qual linha existente de contato/telefone/email corresponde a qual id no banco —
  aceitável por ora já que nada mais referencia essas linhas filhas, mas precisaria mudar se isso deixar de ser
  verdade.
- **Corrida no documento duplicado**: `StoreFornecedorRequest`/`UpdateFornecedorRequest` barram `cnpj_cpf`
  duplicado via `Rule::unique` — mas isso é um SELECT que roda *antes* da transação de `FornecedorService::criar()`/
  `atualizar()`. Duas requisições concorrentes com o mesmo documento novo podem passar ambas por essa validação e
  só colidir no `INSERT`/`UPDATE` em si, estourando a constraint `unique` do banco. `FornecedorService::criar()`/
  `atualizar()` passam pelo helper privado `transacaoComDocumentoUnico()`, que envolve `DB::transaction()` num
  `try/catch` para `Illuminate\Database\UniqueConstraintViolationException` (subclasse dedicada que o Laravel já
  lança nativamente para esse caso, detectada de forma portável tanto no Postgres quanto no sqlite da suíte de
  testes) e relança como `ValidationException` na mesma forma que o `Rule::unique` normal produziria — assim o
  usuário sempre vê "Este(a) CNPJ/CPF já está em uso.", nunca um 500 cru, mesmo na janela de corrida.
- **`situacao_cnpj` nunca é aceito como input do cliente**: é `readonly` só na UI, populado via JS a partir da
  resposta da ReceitaWS (`resources/js/fornecedores/integracao.js`) — sem nenhuma trava no backend, nada impedia
  um POST direto (curl/Postman) setando esse campo pra qualquer texto arbitrário. `StoreFornecedorRequest` não
  tem mais regra de validação para `situacao_cnpj` (então nunca aparece em `$request->validated()`);
  `CnpjController::show()` grava o valor retornado numa consulta real na sessão
  (`session("situacao_cnpj_verificada.{$documento}")`), e `FornecedorService::situacaoCnpjConfiavel()` é o único
  lugar que lê esse valor para persistir — se não há uma consulta real nesta sessão para o CNPJ exatamente
  submetido agora, cai no fallback do valor já persistido (`$fornecedor->situacao_cnpj` em `atualizar()`, `null`
  em `criar()`), nunca em qualquer coisa que tenha vindo do corpo do POST. Isso também evita que editar outros
  campos sem reconsultar o CNPJ apague um `situacao_cnpj` legítimo de uma consulta anterior.
- **Validação de CNPJ/CPF**: `App\Rules\CpfValido` e `App\Rules\CnpjValido` são classes separadas (não uma regra
  única ramificando pelo tamanho) por pedido explícito. `CnpjValido` implementa o formato alfanumérico de CNPJ
  (Nota Técnica COFIS/RFB): os primeiros 12 caracteres podem ser `0-9A-Z`, os 2 dígitos verificadores finais
  continuam numéricos, e o checksum mod-11 usa `ord(caractere) - 48` em vez do dígito literal — isso também
  valida corretamente CNPJs totalmente numéricos do formato antigo como um caso especial. Ambas as regras são
  verificadas contra vetores de teste reais/oficiais conhecidos como válidos em `tests/Unit/Rules/`.
- **Frontend**: `resources/js/fornecedores/` é seu próprio entrypoint do Vite (registrado em `vite.config.js`,
  carregado via `@push('scripts')` apenas na view do formulário de fornecedor) — IMask (máscaras de
  CPF/CNPJ/telefone/CEP) e Quill (o "editor HTML básico" para observações) não entram no bundle global do
  `app.js`, para que outras páginas não paguem o custo deles.
- **`package.json` fixa `"quill": "2.0.2"` exato, sem `^`** — um `^2.0.2` deixaria o npm resolver livremente até
  `2.0.3`, que é exatamente a versão com uma XSS conhecida sem correção disponível:
  [CVE-2025-15056](https://github.com/advisories/GHSA-v3m3-f69x-jf25) (`html()` de
  `formats/formula.ts`/`formats/video.ts` embute valor do usuário na exportação HTML sem escapar). A advisory
  lista só `= 2.0.3` como afetada — `2.0.2` fica de fora do range, e é por isso que a versão aqui é exata, não
  um `^`/`~` qualquer. Reavalie essa trava quando o Quill lançar uma versão corrigida.
- `contatos.js` é o único lugar que sabe renderizar
  uma linha de telefone/email ou um bloco de contato adicional; tanto o botão "Adicionar" quanto o caminho inicial
  de hidratação a partir de dados existentes (modo edição) chamam as mesmas funções de fábrica, então há uma
  única fonte de verdade para esse markup, não templates Blade/JS duplicados. Os próprios dados de hidratação são
  embutidos como um blob `<script type="application/json">` escapado com `JSON_HEX_TAG` — necessário porque ele
  carrega texto livre enviado pelo usuário (nomes de contato, empresas) que poderia quebrar a tag de outra forma.

**Segurança contra XSS armazenado**: nenhum dado de usuário vai para `innerHTML`/`.html()`/template string sem
tratamento — três pontos corrigidos após um code review:

- `resources/js/fornecedores/contatos.js`: os campos de telefone (já estava certo) e agora também e-mail,
  nome/empresa/cargo de contato adicional são atribuídos via `elemento.value = ...` depois de criar o
  `innerHTML` com o campo vazio, nunca interpolados direto na template string do `innerHTML`.
- `resources/js/fornecedores-grid/exclusao.js`: `razao_social_nome` no diálogo de confirmação do SweetAlert2
  passa por `escapeHtml()` (`resources/js/shared/escape-html.js`) antes de entrar no `html:` — esse helper é o
  lugar certo para qualquer outro ponto do projeto que precise interpolar dado de usuário em HTML (em vez de
  reescrever a mesma função em cada entry point do Vite).
- `observacoes` (HTML rico do editor Quill) é sanitizado no **backend**, não só no client — confiar na
  validação client-side aqui seria inútil (dá pra contornar com um POST direto). `FornecedorService::dadosDoFornecedor()`
  passa o valor por `Mews\Purifier\Facades\Purifier::clean()` antes de persistir; `config/purifier.php`'s
  `HTML.Allowed` foi restringido às tags que a toolbar do Quill (`resources/js/fornecedores/editor.js`)
  realmente produz (`h2,h3,p,br,strong,em,u,s,ol,li,ul,blockquote,pre,code,a[href]`) — nada de `script`/`style`/
  `img`/atributos `on*`. Isso fecha o vetor tanto na tela de edição (que faz `dangerouslyPasteHTML` do valor
  salvo) quanto em qualquer uso futuro desse campo fora do editor.

A tabela `users` foi renomeada para `usuarios` com colunas de domínio em pt-BR (`nome`, `senha` em vez de `name`,
`password`) seguindo a convenção de nomenclatura acima — o model `User` mantém seu nome de classe em inglês
(nível de framework) mas define `protected $table = 'usuarios'` e `protected $authPasswordName = 'senha'` para
que os internals de auth do Laravel (validação de credenciais, rehash automático no login) resolvam para a
coluna correta. `Auth::attempt()` ainda é chamado com uma chave de array `'password'` — essa chave é hardcoded
no `EloquentUserProvider` do Laravel, não é o nome da coluna no banco, não "pt-BR-ize" ela.

Não há recuperação de senha: nenhuma tabela `password_reset_tokens`, nenhum broker `passwords` em
`config/auth.php`. Um usuário admin padrão é criado por `database/seeders/UserSeeder.php` (credenciais no
`README.md`) — é um `updateOrCreate`, seguro para rodar de novo.

`bootstrap/app.php` chama `$middleware->redirectGuestsTo('/login')` — sem isso, o middleware `auth` retornaria um
401 puro em vez de redirecionar, já que o middleware `Authenticate` base do Laravel não tem um fallback embutido
de "redirecionar para a rota chamada login" (diferente do middleware `guest`, que já detecta automaticamente a
rota `home`).

### Service → view: view-models de formulário

As ações `create`/`edit` do `FornecedorController` nunca resolvem lógica de `old()`/opções de enum/estado de
seleção por conta própria, e o formulário Blade quase não faz isso também. `FornecedorService::dadosFormulario()`
retorna o blob JSON que o JS do formulário usa para hidratar (dados de contato vindos de old-input ou
persistidos), e `opcoesFormulario()` retorna as opções de cada `<select>` já resolvidas como arrays
`{value, label, selected}` — o Blade só percorre e ecoa, nunca toca em uma classe de enum nem chama `old()`
diretamente para esses campos. Isso foi um refactor deliberado (a view antes construía tudo isso inline) — ao
adicionar um novo `<select>` baseado em enum ao formulário, adicione seu método de opções junto aos já existentes
em `FornecedorService`, não faça um `@foreach (Enum::cases() as ...)` inline no Blade. Campos de texto escalares
simples ainda usam `old('campo', $fornecedor->campo)` diretamente na view — isso é repopulação de formulário
padrão do Laravel, não lógica de negócio, e foi deliberadamente deixado como está.

Todo enum que precisa de um rótulo legível para um desses `<select>`s implementa `App\Contracts\TemRotulo`
(apenas um método `label(): string`) — isso é o que permite que o helper privado `opcoesEnum()` de
`FornecedorService` fique genérico para todos eles em vez de duplicar o mesmo loop de mapear-para-opções por
enum.

### Fornecedores grid (tela de listagem)

`resources/views/fornecedores/index.blade.php` é uma tabela **Tabulator** (`tabulator-tables`, npm), não
DataTables — é o que o próprio `tables/data.html` da pasta do AdminLTE v4 usa, então é o que este projeto usa
também. Busca, ordenação e paginação são todas server-side (`paginationMode`/`sortMode`/`filterMode: 'remote'`);
não há nenhum dado client-side na página, apenas uma `<div id="fornecedores-table">` vazia que busca dados de
`GET /fornecedores/dados`.

- **Protocolo de comunicação**: o modo remoto do Tabulator envia `page`, `size`, `sort[0][field]`/`sort[0][dir]`,
  e `filter[0][field]`/`filter[0][type]`/`filter[0][value]` como query string (confirmado lendo
  `node_modules/tabulator-tables/dist/js/tabulator_esm.js` diretamente — isso não está tão explícito assim na
  documentação pública). Essa query string com array entre colchetes é exatamente o que o `$request->query()` do
  Laravel já parseia em arrays aninhados, e ele espera de volta `{data: [...], last_page: N}` — que é quase
  exatamente o formato do próprio `LengthAwarePaginator`, então `FornecedorController::dados()` quase não
  transforma nada.
- **Busca global** é um campo único, não os filtros de header por coluna do Tabulator: a caixa de busca envia
  `filter[0][field]=busca_global`, um nome de campo que não existe no model — `FornecedorRepository` trata esse
  nome de campo específico como "buscar em razao_social/nome/nome_fantasia/apelido/cnpj_cpf", não uma coluna real.
- **Ordenação é uma allowlist** (`FornecedorRepository::COLUNAS_ORDENAVEIS`), não um passthrough de qualquer nome
  de campo que o cliente enviar — duas das colunas da grid (`razao_social_nome`, `nome_fantasia_apelido`) são
  expressões `COALESCE(...)` sobre duas colunas reais, não nomes de coluna reais, então não poderiam ser passadas
  direto para `orderBy()` mesmo que o nome do campo fosse confiável. Um campo de ordenação não reconhecido é
  silenciosamente ignorado, não um erro — coberto por um teste que tenta especificamente um nome de campo em
  formato de SQL injection.
- **Não use `ilike`** em nenhuma consulta aqui (nem volte a adicioná-lo na busca) — é exclusivo do Postgres e
  quebra de vez na conexão sqlite da suíte de testes. Use `LOWER(coluna) LIKE LOWER(?)` para busca
  case-insensitive portável entre bancos, como `aplicarBusca()` faz.
- **Exclusão é via AJAX, não submit de formulário**: o ícone de lixeira chama `DELETE /fornecedores/{fornecedor}`
  (`FornecedorController::destroy`) via `fetch`, confirmado antes através de um diálogo SweetAlert2
  (`resources/js/fornecedores-grid/exclusao.js`) — nunca um `confirm()` simples ou exclusão imediata. Em caso de
  sucesso, a linha é removida da tabela no local (`row.delete()`) e um toast do Bootstrap é exibido
  (`resources/js/fornecedores-grid/notificacoes.js`) usando o markup de toast do próprio AdminLTE
  (`UI/general.html`), não o modo toast do SweetAlert — o SweetAlert é para o diálogo de confirmação
  especificamente, o padrão de toast é do AdminLTE.
- **`<meta name="csrf-token">`** vive no `<head>` do `<x-layout>` (não por página) — qualquer JS de página fazendo
  um `fetch` não-GET lê o token de lá para o header `X-CSRF-TOKEN`; requisições GET (como o próprio endpoint de
  dados da grid) não precisam disso, o middleware de CSRF do Laravel só protege verbos que alteram estado.
- Isto tem seu próprio entrypoint do Vite (`resources/js/fornecedores-grid/`) pelo mesmo motivo que o formulário
  tem um — Tabulator + SweetAlert2 são grandes e só essa página precisa deles.

### Fornecedores export (PDF/Excel)

Dois botões ao lado de "Novo Fornecedor" na grid (`GET /fornecedores/exportar/pdf` e `/exportar/excel`,
`FornecedorController::exportarPdf()`/`exportarExcel()`) exportam **as mesmas linhas atualmente visíveis na
grid**, não a tabela inteira — o requisito é que a saída da exportação corresponda a qualquer filtro de busca
ativo na tela.

- **O filtro é reaproveitado, a paginação não**: `FornecedorRepository::consultaFiltrada()` é o único método
  privado sobre o qual tanto `paginar()` (grid) quanto `listarParaExportacao()` (export) são construídos — a
  mesma lógica de `aplicarBusca()`/`aplicarOrdenacao()`, então uma nova funcionalidade de busca/ordenação
  adicionada à grid automaticamente se aplica também às exportações. `listarParaExportacao()` apenas chama
  `->get()` nesse builder em vez de `->paginate()` — é deliberadamente sem limite, precisa retornar toda linha
  correspondente, não só a página atual.
- **O frontend monta o estado do filtro manualmente, não a partir de uma URL**: o Tabulator em modo remoto não
  mantém o filtro/ordenação atual na URL da página, então o `montarQueryStringExportacao()` em
  `resources/js/fornecedores-grid/index.js` lê o estado ao vivo diretamente — o valor atual do input
  `#busca-global` e `table.getSorters()` — e o codifica exatamente no mesmo formato de query string
  `filter[0][field]=busca_global&sort[0][field]=...` que as próprias chamadas AJAX da grid já usam, depois
  navega para a URL de exportação (`window.location.href`), confiando no header de resposta
  `Content-Disposition: attachment` para disparar um download em vez de uma navegação de página.
- **Formatação de exibição vive no model, não duplicada por formato de exportação**: `Fornecedor::nome_exibicao`,
  `nome_fantasia_exibicao`, `cnpj_cpf_formatado` são accessors do Eloquent
  (`Illuminate\Database\Eloquent\Casts\Attribute`) usados tanto por `app/Exports/FornecedoresExport.php` (Excel)
  quanto por `resources/views/fornecedores/exportacao-pdf.blade.php` (PDF) — o fallback de razão
  social-ou-nome e a máscara de CPF/CNPJ são escritos uma única vez no model, não reimplementados em cada
  exportação.
- **`app/Exports/` é a própria convenção do Laravel Excel**, não uma violação da camada
  Controller→Service→Repository: uma classe `Export` é um objeto de apresentação parecido com uma view (o que
  `WithMapping`/`WithHeadings` retornam), análogo a uma view Blade ou um API Resource, não lógica de negócio ou
  uma consulta ao banco — ela é construída com uma `Collection` já buscada que o Service produziu. Pelo mesmo
  motivo, a geração de PDF/Excel **não** fica em `Integracoes`: essa pasta é para chamadas HTTP ao vivo a
  terceiros (Brasil API, ViaCEP, ReceitaWS), e gerar um arquivo localmente a partir de dados já buscados não é
  uma chamada a API externa.
- **Proteção contra Formula/CSV Injection**: `razao_social`/`nome_fantasia`/`nome`/`apelido` são texto livre do
  usuário; sem tratamento, um valor como `=HYPERLINK("http://evil.com","clique")` vira fórmula executável ao
  abrir o `.xlsx` no Excel de quem exportou. `FornecedoresExport` implementa `WithCustomValueBinder` e estende
  `DefaultValueBinder` do PhpSpreadsheet: qualquer valor que comece com `=`, `+`, `-` ou `@` é gravado via
  `setValueExplicit(..., DataType::TYPE_STRING)`, forçando o tipo de célula a string independentemente do
  conteúdo — a detecção de fórmula do PhpSpreadsheet nunca roda para esses valores. Confirmado lendo
  `vendor/phpoffice/phpspreadsheet/.../DefaultValueBinder.php` (só marca `TYPE_FORMULA` se o 1º char for `=` E o
  parser conseguir interpretar como fórmula) e verificado end-to-end inspecionando o XML bruto do `.xlsx` gerado
  (`t="s"`, nenhuma tag `<f>`). O PDF não precisa do mesmo tratamento — é texto renderizado em uma view Blade,
  não uma planilha com motor de fórmulas.
- **Testes**: `Excel::fake()` + `Excel::assertDownloaded($nome, fn (FornecedoresExport $export) => ...)` verifica
  o conteúdo real das linhas da exportação Excel sem gerar um arquivo de verdade. Não existe um fake equivalente
  para o DomPDF, então o teste de PDF só faz smoke-test dos headers da resposta (`content-type: application/pdf`,
  `Content-Disposition` tem o nome do arquivo) — a própria lógica de filtragem é coberta uma vez no nível de
  `FornecedorService::listarParaExportacao()` (`tests/Feature/FornecedorExportacaoTest.php`), da qual ambos os
  formatos de exportação dependem de forma idêntica.

### Dados de teste

`LocalidadeSeeder` precisa rodar antes de `php artisan db:seed --class=FornecedorSeeder` (um `migrate --seed`
simples já cobre isso, já que `DatabaseSeeder` chama `LocalidadeSeeder`) — `FornecedorSeeder` escolhe uma
`Cidade` existente aleatória por linha e avisa + encerra se a tabela estiver vazia em vez de criar cidades falsas
silenciosamente. `FornecedorSeeder` é uma ferramenta deliberada de **teste de carga único** (1000 linhas, para
exercitar a busca/paginação da grid sob volume), não faz parte do fluxo padrão de `db:seed` — `DatabaseSeeder`
não o chama. Ele gera valores de CNPJ/CPF falsos únicos a cada execução e não é idempotente, então rodá-lo de
novo contra um banco já semeado arrisca colisões de constraint unique; rode-o em um banco recém-migrado.

`APP_FAKER_LOCALE=pt_BR` (alterado do padrão `en_US` do Laravel) para que todas as chamadas `fake()` das
factories produzam nomes/endereços/empresas com cara brasileira, não americana — importa especialmente para
`FornecedorFactory`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

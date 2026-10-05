# Vercan

Sistema de cadastro e gestão de fornecedores (pessoa física e jurídica) — Laravel 13 (PHP 8.4), PostgreSQL 17,
Nginx, tudo em Docker. Frontend server-rendered com Blade + AdminLTE v4.

Projeto construído como exercício de arquitetura em camadas, segurança aplicada (XSS, SQL injection, Formula
Injection, rate limiting, status HTTP corretos) e qualidade de teste real — não só "ter uma suíte verde". Detalhes
de cada decisão abaixo e em [`docs/`](./docs).

## Tecnologias

**Backend**

- Laravel 13 / PHP 8.4
- PostgreSQL 17 + Nginx + PHP-FPM, tudo via Docker Compose
- [`barryvdh/laravel-dompdf`](https://github.com/barryvdh/laravel-dompdf) — exportação em PDF
- [`maatwebsite/excel`](https://laravel-excel.com/) — exportação em Excel
- [`mews/purifier`](https://github.com/mewebstudio/Purifier) — sanitização de HTML (proteção XSS)
- Laravel Pint (code style) + PHPUnit (testes)

**Frontend**

- Blade (server-rendered) + [AdminLTE v4](https://adminlte.io/) (Bootstrap 5) — sem Tailwind, sem CDN
- Vite 8 — bundling por página (cada feature pesada tem seu próprio entry point)
- [Tabulator](https://tabulator.info/) — datagrid 100% server-side
- [SweetAlert2](https://sweetalert2.github.io/) — confirmações e alerts
- [IMask](https://imask.js.org/) — máscaras de CPF/CNPJ/telefone/CEP
- [Quill](https://quilljs.com/) — editor de texto rico (campo de observações)

## Subindo o ambiente

```bash
cp .env.example .env            # se ainda não existir
docker compose up -d
docker compose exec app php artisan key:generate   # só na primeira vez (.env novo sem APP_KEY)
docker compose exec app php artisan migrate --seed
npm install
npm run build
```

> O `--seed` já importa estados/cidades do Brasil pela Brasil API (`LocalidadeSeeder`) — precisa de acesso à
> internet na primeira vez que roda.

Acesse em [http://localhost:8000](http://localhost:8000) (porta configurável via `FORWARD_APP_PORT` no `.env`).

### Usuário padrão

| Campo  | Valor                 |
| ------ | --------------------- |
| E-mail | `admin@vercan.com.br` |
| Senha  | `Vercan@123`          |

> **Altere essa senha** em qualquer ambiente que não seja desenvolvimento local. Não há recuperação de senha
> implementada — a troca precisa ser feita diretamente no banco (ou via `php artisan tinker`) até que essa
> funcionalidade exista.

### Dados de teste (carga)

```bash
docker compose exec app php artisan db:seed --class=FornecedorSeeder   # 1000 fornecedores fake
```

Depende de estados/cidades já carregados (`LocalidadeSeeder`, que já roda com o `--seed` padrão). Gera CNPJ/CPF
únicos a cada execução — não é idempotente —, e não roda sozinha com `db:seed`/`DatabaseSeeder`; é pra disparar
manualmente numa base recém-migrada.

## Comandos úteis

```bash
# Ambiente
docker compose up -d                 # sobe a stack (dados do banco persistem num volume nomeado)
docker compose stop                  # para os containers, mantém tudo
docker compose exec app bash         # shell dentro do container PHP
docker compose logs -f app|nginx|db  # acompanha logs de um serviço

# Laravel
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed   # CUIDADO: apaga todos os dados
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker

# Qualidade
docker compose exec app php artisan test                   # suíte de testes completa
docker compose exec app php artisan test --filter=NomeDoTeste
docker compose exec app vendor/bin/pint                    # code style (Laravel Pint)

# Frontend (roda no host — Node não está no container)
npm run dev      # Vite com hot reload
npm run build    # build de produção -> public/build/
```

## Documentação

| Arquivo | Conteúdo |
|---|---|
| [`docs/ARQUITETURA.md`](./docs/ARQUITETURA.md) | Camadas, integrações externas, segurança, decisões estruturais |
| [`docs/BANCO_DE_DADOS.md`](./docs/BANCO_DE_DADOS.md) | Diagrama ER e decisões de modelagem |
| [`docs/TESTES.md`](./docs/TESTES.md) | Política de testes — o que testar, o que não, e por quê |
| [`docs/DESTAQUES.md`](./docs/DESTAQUES.md) | Pontos técnicos de destaque (CNPJ alfanumérico, segurança, etc.) |
| [`CLAUDE.md`](./CLAUDE.md) | Referência técnica completa — convenções, gotchas, histórico de decisões |

## Autor

**Paulo Bolsanello**

- LinkedIn: [linkedin.com/in/paulorbolsanello](https://www.linkedin.com/in/paulorbolsanello/)
- GitHub: [github.com/paulodm145](https://github.com/paulodm145)
- Blog: [paulorb.dev](https://paulorb.dev/)
- E-mail: [paulo.bolsanello@gmail.com](mailto:paulo.bolsanello@gmail.com)

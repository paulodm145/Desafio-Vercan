# Política de testes

```bash
docker compose exec app php artisan test                      # suíte completa
docker compose exec app php artisan test --filter=NomeDoTeste  # um teste específico
docker compose exec app php artisan test tests/Feature/Arquivo.php
```

PHPUnit, rodando contra **sqlite `:memory:`** — independente do Postgres do `docker compose`, não precisa do
container `db` no ar pra rodar a suíte.

## O princípio

**Teste verde não é sinônimo de código correto.** Não se escreve teste pra "ter cobertura", e não se apaga/pula
teste só pra suíte passar — cada teste existe pra proteger um comportamento real, e um teste falhando é um
requisito não atendido, nunca um obstáculo a ser contornado.

- Só vale testar cenários que pegariam um **bug real e de alto impacto** se quebrassem — comportamento de
  auth/sessão, constraints de banco, casos de borda de regra de negócio. Pula-se o que o próprio Laravel já
  garante (roteamento, regras de validação funcionando, uma view renderizando sem lógica) e código trivial sem
  ramificação.
- **Valor esperado é sempre um literal da regra de negócio, nunca recalculado** a partir de uma constante ou
  método da própria classe testada. Os vetores de teste de CPF/CNPJ, por exemplo, são documentos
  reais/oficiais (Nota Técnica COFIS/RFB) — nunca um dígito verificador calculado pela própria `Rule` sendo
  testada, o que aprovaria o mesmo bug que o teste deveria pegar.
- **Asserção específica o bastante pra quebrar se o comportamento quebrar** — `assertNotNull`/`assertTrue(true)`
  isolados não protegem nada; só valem como checagem de pré-condição antes de uma asserção forte.
- **Comportamento observável, não implementação** — não mocka o objeto sendo testado, não deve quebrar com uma
  refatoração válida que preserva o resultado.
- Tudo bem verificar comportamento de forma ad hoc enquanto constrói uma funcionalidade (curl, tinker, execuções
  avulsas) sem transformar cada checagem num teste commitado — a suíte permanente fica enxuta, não um registro de
  tudo que já foi checado manualmente pelo caminho.

## Em números

~46 testes, cobrindo (entre outros): autenticação e rate limiting do login, CRUD completo de fornecedores com
contatos/telefones/e-mails aninhados, condição de corrida em documento duplicado, sanitização de XSS em
observações, confiança de `situacao_cnpj` só vinda de consulta real à ReceitaWS, grid (busca/ordenação/allowlist
contra SQL injection), exportação PDF/Excel respeitando filtro em tela, proteção contra Formula Injection, status
HTTP correto em cada integração externa (200/404/503).

## Unit vs Feature

- `tests/Unit/` — regras isoladas sem tocar banco/rede (ex.: `CpfValido`/`CnpjValido`, o value binder do Excel).
- `tests/Feature/` — fluxo completo via HTTP (rotas, middleware, banco sqlite em memória).

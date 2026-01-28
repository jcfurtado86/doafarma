# Backlog Generation Log

**Data:** 2026-01-27
**Baseado em:** docs/CODEBASE-HEALTH-REPORT.md
**Repositório:** jcfurtado86/doafarma

---

## Resumo

✅ **31 issues criadas** (algumas duplicadas foram corrigidas)

**Issues únicas finais: 31 (de #80 a #110)**

### Por Prioridade

- 🔴 **CRÍTICO:** 5 issues (blockers de produção)
- 🟠 **ALTA:** 7 issues (pré-produção)
- 🟡 **MÉDIA:** 10 issues (melhorias importantes)
- 🟢 **BAIXA:** 3 issues (polish)

### Por Categoria

- 🔒 **Segurança:** 5 issues
- ⚡ **Performance:** 5 issues
- 🎨 **UX:** 6 issues
- 🔧 **Code Quality/Refactor:** 5 issues
- 👨‍💻 **Developer Experience:** 5 issues
- ♿ **Acessibilidade:** 1 issue
- 📚 **Documentação:** 2 issues
- 🧪 **Testing:** 2 issues

### Por Esforço

- 🟢 **Pequeno (<2h):** 13 issues
- 🟡 **Médio (2-8h):** 12 issues
- 🔴 **Grande (>8h):** 6 issues

---

## Issues Criadas (em ordem de prioridade)

### 🔴 CRÍTICO - Sprint 1 (OBRIGATÓRIO antes de produção)

#### [#80](https://github.com/jcfurtado86/doafarma/issues/80) - Tokens de autenticação sem expiração
- **Labels:** security, priority:critical, effort:small, tech-debt
- **Esforço:** 5 minutos
- **Impacto:** Vulnerabilidade crítica OWASP A07

#### [#81](https://github.com/jcfurtado86/doafarma/issues/81) - Remover dados fictícios hardcoded do registro de médico
- **Labels:** bug, priority:critical, effort:small, ux
- **Esforço:** 30 minutos
- **Impacto:** Sistema não funcional para produção

#### [#82](https://github.com/jcfurtado86/doafarma/issues/82) - Adicionar todos os 27 estados brasileiros no select de UF
- **Labels:** bug, priority:critical, effort:small, ux
- **Esforço:** 10 minutos
- **Impacto:** 81% dos médicos não conseguem se registrar

#### [#83](https://github.com/jcfurtado86/doafarma/issues/83) - Mover rotas de doctor-ratings para middleware approved
- **Labels:** security, priority:critical, effort:small, tech-debt
- **Esforço:** 15 minutos
- **Impacto:** Vulnerabilidade controle de acesso OWASP A01

#### [#84](https://github.com/jcfurtado86/doafarma/issues/84) - Corrigir N+1 queries em Filament Resources
- **Labels:** performance, priority:critical, effort:small, tech-debt
- **Esforço:** 15 minutos
- **Impacto:** Admin lento (300+ queries, +1-4s)

---

### 🟠 ALTA PRIORIDADE - Sprint 2 (Antes de produção)

#### [#85](https://github.com/jcfurtado86/doafarma/issues/85) - Implementar rate limiting em endpoints sensíveis
- **Labels:** security, priority:high, effort:small, improvement
- **Esforço:** 30 minutos
- **Impacto:** Proteção contra flood/abuso

#### [#86](https://github.com/jcfurtado86/doafarma/issues/86) - Adicionar paginação em listagens de API
- **Labels:** performance, priority:high, effort:medium, tech-debt
- **Esforço:** 2-4 horas
- **Impacto:** Performance com 1000+ registros

#### [#87](https://github.com/jcfurtado86/doafarma/issues/87) - Substituir Alerts por Toasts em notificações de sucesso
- **Labels:** ux, priority:high, effort:medium, improvement
- **Esforço:** 3-4 horas
- **Impacto:** UX mobile-friendly

#### [#88](https://github.com/jcfurtado86/doafarma/issues/88) - Quebrar componentes monolíticos do mobile
- **Labels:** refactor, priority:high, effort:large, tech-debt
- **Esforço:** 8-16 horas
- **Impacto:** Manutenibilidade e escalabilidade

#### [#89](https://github.com/jcfurtado86/doafarma/issues/89) - Criar README principal com setup completo
- **Labels:** documentation, dx, priority:high, effort:small
- **Esforço:** 1 hora
- **Impacto:** Onboarding de 60min → 10min

#### [#90](https://github.com/jcfurtado86/doafarma/issues/90) - Adicionar acessibilidade em componentes principais
- **Labels:** a11y, priority:high, effort:medium, improvement
- **Esforço:** 3-4 horas
- **Impacto:** Inclusão de usuários com deficiência

#### [#91](https://github.com/jcfurtado86/doafarma/issues/91) - Corrigir 8 erros TypeScript e bloquear no pre-commit
- **Labels:** dx, priority:high, effort:small, tech-debt
- **Esforço:** 1-2 horas
- **Impacto:** Type safety e prevenção de bugs

---

### 🟡 MÉDIA PRIORIDADE - Sprint 3 (Melhorias importantes)

#### [#92](https://github.com/jcfurtado86/doafarma/issues/92) - Centralizar eager loading em scope do Model
- **Labels:** refactor, priority:medium, effort:small, tech-debt
- **Esforço:** 1 hora
- **Impacto:** DRY - evitar repetição em 9 Actions

#### [#93](https://github.com/jcfurtado86/doafarma/issues/93) - Configurar CORS de forma específica
- **Labels:** security, priority:medium, effort:small, tech-debt
- **Esforço:** 30 minutos
- **Impacto:** Segurança de configuração

#### [#94](https://github.com/jcfurtado86/doafarma/issues/94) - Adicionar memoization em componentes React
- **Labels:** performance, priority:medium, effort:medium, improvement
- **Esforço:** 2-3 horas
- **Impacto:** Scroll mais fluido (60ms → 10ms)

#### [#95](https://github.com/jcfurtado86/doafarma/issues/95) - Implementar detecção de conexão offline
- **Labels:** ux, priority:medium, effort:large, improvement
- **Esforço:** 4-6 horas
- **Impacto:** UX em áreas com internet instável

#### [#96](https://github.com/jcfurtado86/doafarma/issues/96) - Adicionar headers de segurança HTTP
- **Labels:** security, priority:medium, effort:small, tech-debt
- **Esforço:** 1 hora
- **Impacto:** Proteção adicional (XSS, clickjacking)

#### [#97](https://github.com/jcfurtado86/doafarma/issues/97) - Converter select de cidades em input de texto livre
- **Labels:** ux, priority:medium, effort:small, improvement
- **Esforço:** 30 minutos
- **Impacto:** Remover select hardcoded inútil

#### [#98](https://github.com/jcfurtado86/doafarma/issues/98) - Criar .env.local.example no mobile com documentação
- **Labels:** documentation, dx, priority:medium, effort:small
- **Esforço:** 20 minutos
- **Impacto:** Elimina erro #1 de setup

#### [#99](https://github.com/jcfurtado86/doafarma/issues/99) - Adicionar índices em colunas frequentemente buscadas
- **Labels:** performance, priority:medium, effort:small, tech-debt
- **Esforço:** 10 minutos (migration)
- **Impacto:** Queries mais rápidas

#### [#100](https://github.com/jcfurtado86/doafarma/issues/100) - Implementar CI/CD com GitHub Actions
- **Labels:** dx, priority:medium, effort:medium, improvement
- **Esforço:** 3-4 horas
- **Impacto:** Automação de qualidade

#### [#107](https://github.com/jcfurtado86/doafarma/issues/107) - Implementar CI/CD (duplicada de #100)
- **Status:** Duplicada - fechar

---

### 🟢 BAIXA PRIORIDADE - Backlog (Nice to have)

#### [#108](https://github.com/jcfurtado86/doafarma/issues/108) - Centralizar validações com Zod no frontend
- **Labels:** refactor, priority:low, effort:medium, tech-debt
- **Esforço:** 3-4 horas
- **Impacto:** Validações reutilizáveis

#### [#109](https://github.com/jcfurtado86/doafarma/issues/109) - Remover console.log de produção
- **Labels:** tech-debt, priority:low, effort:small, improvement
- **Esforço:** 1 hora
- **Impacto:** Logs limpos em produção

#### [#110](https://github.com/jcfurtado86/doafarma/issues/110) - Substituir 'any' por tipos apropriados
- **Labels:** tech-debt, priority:low, effort:medium, improvement
- **Esforço:** 2-3 horas
- **Impacto:** Type safety melhorado

---

## Issues Faltantes (Não criadas ainda)

As seguintes issues mencionadas no relatório de saúde NÃO foram criadas (ficaram para próxima rodada):

- [ ] Skeleton loaders (#10 do relatório)
- [ ] Contraste de cores WCAG (#9 do relatório)
- [ ] Áreas de toque 44pt (#7 do relatório)
- [ ] Toggle de senha no registro médico (#4 do relatório)
- [ ] Normalização de email (#11 do relatório)
- [ ] Termos de uso clicáveis
- [ ] Indicador de progresso em multi-step forms
- [ ] Select de cidades dinâmico (integração IBGE)

**Razão:** Foco em problemas críticos e alta prioridade primeiro.

---

## Próximos Passos Recomendados

### 1. Sprint 1 (1 semana) - BLOCKERS
Resolver issues #80-#84 (5 críticas)
- Estimativa: 1-2 dias de trabalho
- **OBRIGATÓRIO** antes de deploy em produção

### 2. Sprint 2 (2 semanas) - PRÉ-PRODUÇÃO
Resolver issues #85-#91 (7 alta prioridade)
- Estimativa: 4-5 dias de trabalho
- Preparação final para produção

### 3. Sprint 3 (2 semanas) - CONSOLIDAÇÃO
Resolver issues #92-#100 (9 média prioridade)
- Estimativa: 1 semana de trabalho
- Melhorias de arquitetura e performance

### 4. Backlog - POLISH
Resolver issues #108-#110 quando houver tempo
- Refinamentos e polish

---

## Métricas de Progresso

Use as seguintes métricas para acompanhar melhorias:

| Métrica | Antes | Meta |
|---------|-------|------|
| **Segurança** |
| Tokens com expiração | ❌ | ✅ |
| Vulnerabilidades críticas | 2 | 0 |
| Rate limiting | 10% endpoints | 100% |
| **Performance** |
| Queries Filament (50 items) | ~300 | <10 |
| Tempo médio API | ~2s | <500ms |
| Componentes memoizados | 0% | 80% |
| **UX** |
| Estados brasileiros | 5 (19%) | 27 (100%) |
| Dados fictícios | Sim ❌ | Não ✅ |
| Componentes a11y | 2% | 100% |
| **DX** |
| Tempo setup | 60min | 10min |
| Erros TypeScript | 8 | 0 |
| Cobertura testes mobile | 0% | >50% |

---

## Labels Criadas

- `security` - Segurança
- `performance` - Performance
- `ux` - User Experience
- `dx` - Developer Experience
- `a11y` - Acessibilidade
- `testing` - Testes
- `tech-debt` - Débito técnico
- `refactor` - Refatoração
- `improvement` - Melhoria
- `documentation` - Documentação
- `priority:critical` - Blocker de produção
- `priority:high` - Alta prioridade
- `priority:medium` - Média prioridade
- `priority:low` - Baixa prioridade
- `effort:small` - <2h
- `effort:medium` - 2-8h
- `effort:large` - >8h

---

## Links Úteis

- 📊 [Ver todas as issues](https://github.com/jcfurtado86/doafarma/issues)
- 📋 [Issues críticas](https://github.com/jcfurtado86/doafarma/issues?q=is%3Aissue+is%3Aopen+label%3Apriority%3Acritical)
- 🔥 [Issues de alta prioridade](https://github.com/jcfurtado86/doafarma/issues?q=is%3Aissue+is%3Aopen+label%3Apriority%3Ahigh)
- 📖 [Relatório de Saúde Completo](docs/CODEBASE-HEALTH-REPORT.md)

---

*Log gerado automaticamente em 2026-01-27*

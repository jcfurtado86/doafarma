# Product Discovery — DoaFarma — 2026-02-26

## 1. Contexto

| Item | Detalhe |
|------|---------|
| Estágio | TCC finalizado, todas as funcionalidades solicitadas entregues. Transição para produto real. |
| Motivação | Orientador incentiva transformar em produto. Liberdade total para novas features. |
| Prazo | Nenhum deadline fixo. TCC ainda não apresentado oficialmente. |
| Feedback de usuários reais | Nenhum. Não houve teste com médicos ou receptores reais. |
| Restrição financeira | Orçamento zero — priorizar soluções gratuitas, pagar só quando inevitável. |
| Time | Solo dev (Kauê) + AI tools. Backend Laravel é o forte, mobile se vira. |
| Monetização | Postergada até após apresentação do TCC. |

---

## 2. Visão

### 6-12 meses

O DoaFarma se torna um **app personalizado de saúde** que as pessoas querem ter no celular — não apenas um marketplace de doação, mas um companheiro que conhece o usuário, cuida dele e conecta quem precisa com quem pode ajudar. A referência é o app **Flo**: algo que gera valor diário e por isso as pessoas mantêm instalado.

### North Star Metric

**Doações concluídas por mês** — indica que o ciclo completo funciona (cadastro → busca → match → encontro → entrega).

### Anti-goal

Qualquer um dos lados (médico ou receptor) parar de engajar. Sem os dois lados ativos, nada funciona. O DoaFarma fracassa se virar um app que ninguém abre depois da primeira vez.

---

## 3. Personas

### Médico (Doador)

**Quem é**: Médico que possui medicamentos excedentes (amostras grátis, sobras de tratamento) e quer doá-los.

**Jornada atual**:
1. Baixa o app → cadastra como médico (dados pessoais + CRM + UF)
2. Aguarda aprovação manual do admin
3. Cadastra medicamento: busca na base oficial do governo, seleciona, informa lote/validade/quantidade
4. Medicamento fica disponível no catálogo
5. Recebe solicitações de receptores → aceita ou rejeita
6. Fase de agendamento: combina horário e local
7. Encontro presencial (sem suporte do sistema)
8. Recebe notificações de lembrete e alerta de validade

**Dores mapeadas**:
- Sem validação real de CRM — qualquer pessoa pode se cadastrar como médico
- Sem upload de foto do medicamento
- Sem padrão oficial de lote
- Não consegue atualizar endereço depois do cadastro
- Do agendamento pra frente, o sistema abandona o usuário: sem mapa, sem direções, sem confirmação de que o encontro aconteceu
- Tela do médico visualmente inferior (botões soltos vs. tab navigation do receptor)

**O que o traria de volta**: Pergunta em aberto. Não há resposta clara ainda. Ideia exploratória: teleconsulta como regra de negócio paralela.

**O que o faria sair**: Processo burocrático demais (aprovação lenta), app confuso ou feio, insegurança no encontro.

### Receptor (quem recebe a doação)

**Quem é**: Pessoa que precisa de medicamentos e não tem condições financeiras de comprá-los. Dois perfis identificados:

1. **Receptor pontual**: doença temporária (gripe, infecção), precisa de algo agora
2. **Receptor crônico**: condição permanente (lúpus, diabetes), precisa do mesmo medicamento recorrentemente

**Jornada atual**:
1. Baixa o app → cadastra como receptor
2. Aguarda aprovação manual do admin
3. Vê lista de medicamentos disponíveis
4. Solicita um medicamento → fica esperando o médico aceitar (sem feedback claro)
5. Médico aceita → fase de agendamento (propostas de local e data)
6. Encontro presencial (sem suporte do sistema)
7. Confirma que recebeu a doação
8. Avalia o médico (nota + comentário)

**Dores mapeadas**:
- Aprovação manual desnecessária para receptor (é quem recebe, não precisa ser validado)
- Fica "largado" esperando aceitação do médico sem saber o status
- Sem personalização: o app não sabe do que ele precisa recorrentemente
- Sem busca por proximidade geográfica
- Encontro sem qualquer suporte do sistema (mesmas dores do médico)

**O que o traria de volta**: App que "cuida de mim" — monitora condições, notifica quando medicamento que precisa está disponível, experiência personalizada.

**O que o faria sair**: Não encontrar o medicamento que precisa, processo inseguro de encontro, app que não traz valor fora do momento da doação.

### Admin

**Quem é**: Hoje é o próprio Kauê. No futuro, equipe de operações.

**Jornada atual**: Aprova usuários manualmente no Filament. Visualiza dashboards e logs de atividade.

**Dores**: Não consegue gerenciar base de medicamentos nem avaliações pelo painel. Aprovação manual é gargalo. Sem suporte a múltiplos admins.

---

## 4. Mapa de Oportunidades

### A: Expansão de Funcionalidades Core

| # | Oportunidade | Origem |
|---|---|---|
| A1 | CRUD de endereços + local de encontro padrão (com opção de alterar por agendamento) | Auditoria gap #2 + entrevista |
| A2 | Upload de foto do medicamento | Entrevista |
| A3 | Configurações pessoais do perfil (editar dados pós-cadastro) | Entrevista |
| A4 | Melhorar fluxo pós-match (encontro + entrega) — escopo a definir | Entrevista |

### B: Experiência do Usuário

| # | Oportunidade | Origem |
|---|---|---|
| B1 | Definir design system (padrão visual, componentes, cores) baseado em inspirações do Kauê | Entrevista |
| B2 | Padronizar navegação do médico (tab navigation como receptor) | Auditoria gap #20 + entrevista |
| B3 | Telas de estado vazio para todas as listas | Auditoria gap #8 |
| B4 | Redesign geral das telas seguindo o novo padrão | Entrevista |

### C: Engajamento & Retenção

| # | Oportunidade | Origem |
|---|---|---|
| C1 | App como "companheiro de saúde" — monitoramento, lembretes, sensação de cuidado | Entrevista (referência: app Flo) |
| C2 | Notificações proativas personalizadas ("apareceu o medicamento que você precisa") | Entrevista |
| C3 | Teleconsulta / atendimentos online (nova regra de negócio) | Entrevista — **postergado até após TCC** |
| C4 | "O que traz o médico de volta?" — pergunta em aberto, lacuna de conhecimento | Entrevista |

### D: Confiança & Segurança

| # | Oportunidade | Origem |
|---|---|---|
| D1 | Validação de CRM contra API/site oficial (ex: portal CFM) | Entrevista |
| D2 | Perfil público do médico com selo "verificado" + avaliações visíveis | Entrevista |
| D3 | Sistema de denúncia/report com consequências (suspensão, banimento) | Entrevista |
| D4 | Rate limiting no login + reset de senha mobile | Auditoria gaps #1 e #4 |

### E: Eficiência Operacional

| # | Oportunidade | Origem |
|---|---|---|
| E1 | Auto-aprovação de médico via validação de CRM por API | Entrevista (analogia Nubank vs BB) |
| E2 | Remover aprovação para receptores | Entrevista |
| E3 | Admin com CRUD completo de todas as entidades | Auditoria + entrevista |
| E4 | Suporte a múltiplos admins com papéis/permissões | Entrevista |

### F: Crescimento & Aquisição

| # | Oportunidade | Origem |
|---|---|---|
| F1 | Parcerias institucionais (governo, secretarias de saúde, hospitais públicos) | Entrevista |
| F2 | Parcerias com conselhos médicos / associações | Entrevista |
| F3 | Estratégia de aquisição — **lacuna de conhecimento**, precisa de alguém de marketing/growth | Entrevista |

### G: Monetização

| # | Oportunidade | Origem |
|---|---|---|
| G1 | Teleconsulta como modelo de receita (médico atende, receptor paga, plataforma fica com %) | Entrevista — **postergado** |
| G2 | Explorar modelos de monetização que não comprometam a missão social | Entrevista — **postergado** |

### H: Habilitação Técnica

| # | Oportunidade | Origem |
|---|---|---|
| H1 | Geolocalização — busca por proximidade | Entrevista |
| H2 | Infra de upload de imagens (fotos de medicamento) | Entrevista |
| H3 | Notificações personalizadas por perfil/condição do receptor | Entrevista |
| H4 | Chat contextual (escopo limitado a fase ativa do processo, modelo Uber/iFood) | Entrevista — requer design cuidadoso |

---

## 5. Priorização (RICE + KILL)

### P0 — Construir Agora

| # | Oportunidade | R | I | C | E | RICE | Justificativa |
|---|---|---|---|---|---|---|---|
| E2 | Remover aprovação para receptores | 10 | 4 | 1.0 | 1 | 40.0 | Esforço mínimo, elimina fricção de onboarding para 100% dos receptores |
| D4 | Rate limiting login + reset senha mobile | 10 | 3 | 1.0 | 2 | 15.0 | Segurança básica obrigatória — auditoria classificou como P0 |
| B1 | Definir design system | 10 | 3 | 0.8 | 3 | 8.0 | Desbloqueia todo trabalho visual futuro, evita retrabalho |
| A1 | CRUD de endereços + local padrão | 7 | 4 | 1.0 | 4 | 7.0 | Desbloqueia fluxo de encontro, corrige bug silencioso do `->first()` |
| E1 | Auto-aprovação médico via CRM | 6 | 4 | 0.8 | 4 | 4.8 | Elimina gargalo admin, onboarding instantâneo como Nubank |

### P1 — Próximo Ciclo

| # | Oportunidade | R | I | C | E | RICE |
|---|---|---|---|---|---|---|
| B2 | Padronizar navegação do médico (tabs) | 6 | 3 | 1.0 | 2 | 9.0 |
| B3 | Telas de estado vazio | 10 | 2 | 1.0 | 1 | 20.0 |
| A3 | Configurações pessoais do perfil | 8 | 2 | 1.0 | 3 | 5.3 |
| E3 | Admin CRUD completo | 5 | 3 | 1.0 | 3 | 5.0 |
| D2 | Perfil público médico + selo verificado | 6 | 3 | 0.8 | 3 | 4.8 |
| D1 | Validação CRM contra API oficial | 6 | 4 | 0.5 | 5 | 2.4 |
| H1 | Geolocalização (busca por proximidade) | 8 | 4 | 0.8 | 6 | 4.3 |
| A2 | Upload de foto de medicamento | 7 | 3 | 0.8 | 4 | 4.2 |
| C2 | Notificações proativas personalizadas | 7 | 3 | 0.8 | 4 | 4.2 |
| H3 | Notificações personalizadas por perfil | 7 | 3 | 0.8 | 4 | 4.2 |
| D3 | Sistema de denúncia/report | 7 | 3 | 0.8 | 4 | 4.2 |

### P2 — Futuro

| # | Oportunidade | RICE | KILL Filter |
|---|---|---|---|
| B4 | Redesign geral das telas | 3.4 | — |
| H2 | Infra de upload de imagens | 4.7 | — |
| A4 | Fluxo pós-match (entrega) | 2.8 | Escopo indefinido |
| C1 | App companheiro de saúde | 2.2 | Solo dev overreach |
| H4 | Chat contextual | 1.7 | Design needed antes de implementar |
| E4 | Multi-admin com papéis | 1.5 | Premature — escala não existe |
| C3 | Teleconsulta | 1.7 | Postergado até após TCC |
| G1-G2 | Monetização | — | Postergado até após TCC |
| F1-F3 | Estratégia de aquisição | — | Knowledge gap |
| C4 | Retenção do médico | — | Knowledge gap |

---

## 6. Restrições

| Restrição | Detalhe |
|-----------|---------|
| Time | Solo dev + AI tools. Backend Laravel forte, mobile se vira. |
| Prazo | Sem deadline. TCC ainda não apresentado. |
| Orçamento | Zero. Soluções gratuitas prioritárias. |
| Skills ausentes | Marketing/growth, UX research, conhecimento médico profundo |
| Exclusão explícita | Monetização postergada até após apresentação do TCC |
| Dependência externa | Validação de CRM depende de existir API acessível do CFM |

---

## 7. Notas da Entrevista

### Citações-chave

> "Doação normalmente tem uma aversão com a parte financeira. Se tem doação, é de graça, então não tem dinheiro."
— Sobre a tensão doação vs. negócio

> "A gente ainda não tem um médico ali pra ser o nosso teste. A gente ainda não entende 100% as dores que o médico teria nesse processo."
— Lacuna de conhecimento reconhecida sobre o lado do médico

> "Se eu fosse um usuário, eu mesmo reconheço que eu ia odiar essa parte [do encontro]. Talvez eu nem fosse no lugar marcado."
— Sobre o fluxo pós-agendamento

> "As pessoas iam querer ter porque ia ajudar muito na vida delas. Por exemplo, muitas mulheres usam aquele Flo pra ciclo menstrual. Dá certo porque ajuda muito elas no dia a dia mesmo."
— Referência para engajamento e retenção

> "Se for pra ter um chat, tem que entender em qual momento é que tem que ter o chat. A gente não pode perder a nossa visibilidade do que tá acontecendo."
— Reflexão madura sobre chat vs. fluxos estruturados

> "Entre um Banco do Brasil da vida digital e um Nubank, eu prefiro muito mais o Nubank, porque as coisas se resolvem muito mais rápido."
— Sobre onboarding sem fricção

> "Acho que é aí que a gente vai começar a ter as dores de ter um negócio."
— Sobre estratégia de aquisição

### Decisões tomadas

1. Monetização postergada até após apresentação do TCC
2. Chat requer design cuidadoso antes de implementação — risco de tornar funcionalidades estruturadas obsoletas
3. Receptor não precisa de aprovação — apenas médico
4. Design system deve ser definido antes do redesign geral
5. Pergunta "o que traz o médico de volta?" permanece aberta — requer pesquisa com médicos reais

### Lacunas de conhecimento identificadas

1. **Dores reais do médico** — nunca houve contato com médicos reais
2. **Retenção do médico** — sem ideia clara do que o faria voltar ao app recorrentemente
3. **Estratégia de aquisição** — não sabe como atrair os primeiros usuários, precisa de apoio de marketing
4. **Fluxo de entrega** — segurança, logística e confiança são todas dores enormes, mas sem definição de solução
5. **Padrão de lote de medicamento** — não conhece o padrão oficial
6. **Medicamentos com prescrição médica** — como lidar com esse caso no futuro
7. **Validação de CRM via API** — não sabe se existe API acessível do CFM

---

**Próximo passo**: Run `/epic-generator docs/discovery/product-discovery-2026-02-26.md` para transformar essas oportunidades em epics estruturados para o board do projeto.

# Workflow de Preparação para Teste Manual

**Data:** 2026-01-24
**Contexto:** Processo de PR review
**Tags:** workflow, testing, doafarma

## Situação

Ao finalizar uma implementação e abrir uma PR, o usuário precisa testar manualmente a funcionalidade antes de aprovar. É necessário preparar o ambiente para que o teste seja fácil e rápido.

## Checklist de Preparação para Teste Manual

### 1. Backend (Laravel)

Antes de abrir a PR, garantir que:

```bash
# Na pasta web/
composer install          # Dependências atualizadas
php artisan migrate       # Migrations aplicadas
php artisan db:seed       # Dados de teste (se necessário)
```

**Se a feature criar novo endpoint:**
- Documentar o endpoint no corpo da PR
- Incluir exemplo de request/response

### 2. Mobile (React Native/Expo)

```bash
# Na pasta mobile/
npm install               # Dependências atualizadas
```

**Preparar para rodar:**
```bash
npm start                 # Inicia o Expo
```

### 3. Dados de Teste

Se a feature precisa de dados específicos (ex: histórico de medicamentos precisa de appointments completados):

1. Verificar se existem factories/seeders adequados
2. Criar um seeder específico se necessário
3. Documentar como popular os dados de teste na PR

### 4. Na PR, Incluir Seção de Teste Manual

Adicionar ao corpo da PR:

```markdown
## Como Testar Manualmente

### Pré-requisitos
- [ ] Backend rodando: `cd web && php artisan serve`
- [ ] Mobile rodando: `cd mobile && npm start`
- [ ] Dados necessários: [explicar quais]

### Passos para Testar
1. Fazer login como receptor
2. Navegar para a aba "Histórico"
3. Verificar que...

### Cenários a Validar
- [ ] Com dados: exibe lista corretamente
- [ ] Sem dados: mostra estado vazio
- [ ] Pull-to-refresh funciona
```

## Próximos Passos

Em futuras implementações, SEMPRE incluir a seção "Como Testar Manualmente" na PR antes de perguntar ao usuário o que fazer.

# Atualizacao de Dependencias - Janeiro 2026

Este documento lista as atualizacoes realizadas no projeto DoaFarma e as novas ferramentas/features disponiveis.

## Resumo das Atualizacoes

### Backend (Laravel/PHP)

| Pacote | Versao Anterior | Nova Versao | Tipo |
|--------|-----------------|-------------|------|
| Laravel Framework | 12.26.x | 12.48.1 | Patch |
| Filament | 3.3.x | 4.6.1 | **Major** |
| Larastan | 3.0.x | 3.9.1 | Minor |
| Laravel Boost | 1.1.x | 2.0.2 | **Major** |
| Laravel Pint | 1.13.x | 1.27.0 | Minor |
| Laravel Sail | 1.26.x | 1.52.0 | Minor |
| Pest | 4.x | 4.3.1 | Patch |
| Rector | 2.1.x | 2.3.4 | Minor |
| Scramble (API Docs) | 0.12.9 | 0.13.10 | Minor |
| Laravel Tinker | 2.10.x | 2.11.0 | Minor |

### Mobile (Expo/React Native)

| Pacote | Versao Anterior | Nova Versao | Tipo |
|--------|-----------------|-------------|------|
| Expo SDK | 54.0.25 | 54.0.32 | Patch |
| Expo Router | 6.0.15 | 6.0.22 | Patch |
| React Hook Form | 7.54.2 | 7.71.0 | Minor |
| Zod | 3.24.2 | 3.25.0 | Minor |
| Zustand | 5.0.3 | 5.0.10 | Patch |
| Axios | 1.8.3 | 1.13.0 | Minor |
| @expo-google-fonts/roboto | 0.2.3 | 0.4.0 | Minor |
| @react-navigation/native | 7.0.0 | 7.1.0 | Minor |
| React Native Reanimated | 4.1.1 | 4.1.6 | Patch |

---

## Novas Features Disponiveis

### 1. Laravel Boost v2.0 (Backend)

O Laravel Boost v2.0 traz melhorias significativas para o fluxo de desenvolvimento:

**Novidades principais:**
- **Suporte a Laravel 12+**: Totalmente compativel com a versao mais recente do Laravel
- **Melhoria de performance**: Reducao no tempo de inicializacao do servidor de desenvolvimento
- **Hot Module Replacement aprimorado**: Recarregamento mais rapido durante desenvolvimento
- **Integracao com Vite 6**: Suporte completo ao Vite 6 para builds mais rapidos

**Como usar:**
```bash
# O script dev ja usa o Boost
composer dev

# Ou execute individualmente
php artisan serve
```

---

### 2. Larastan v3.9 (Analise Estatica)

Novo nivel de analise estatica para PHP com regras mais estritas:

**Novidades:**
- **Suporte a PHP 8.4**: Analise completa de features novas do PHP 8.4
- **Regras para Laravel 12**: Deteccao de problemas especificos do Laravel 12
- **Inferencia de tipos melhorada**: Menos falsos positivos em closures e generics
- **Performance**: ~30% mais rapido em analises incrementais

**Configuracao atual:** `phpstan.neon`

---

### 3. Laravel Pint v1.27 (Code Style)

Novas regras e melhorias no formatador de codigo:

**Novidades:**
- **Regras PSR-12 atualizadas**: Conformidade total com PSR-12
- **Suporte a atributos PHP 8.x**: Formatacao correta de #[Attribute]
- **Preset Laravel atualizado**: Segue as convencoes mais recentes do Laravel

**Como usar:**
```bash
composer pint
```

---

### 4. Rector v2.3 (Refactoring Automatico)

Ferramenta de refatoracao automatica com novas regras:

**Novidades:**
- **Deprecacao de strictBooleans set**: Usar `codeQuality` e `codingStyle` em vez disso
- **PHP 8.4 rules**: Conversao automatica para sintaxe PHP 8.4
- **Laravel 12 rules**: Atualizacoes automaticas de codigo deprecated

**Como usar:**
```bash
./vendor/bin/rector
```

---

### 5. Scramble v0.13 (API Documentation)

Geracao automatica de documentacao OpenAPI:

**Novidades:**
- **Suporte a Laravel 12**: Compatibilidade total
- **Melhoria em type inference**: Documentacao mais precisa de tipos
- **Suporte a Form Requests**: Melhor documentacao de validacoes
- **UI atualizada**: Interface Swagger/ReDoc mais moderna

**Acesso:** `http://localhost:8000/docs/api`

---

### 6. React Hook Form v7.71 (Mobile)

Melhorias no gerenciamento de formularios:

**Novidades:**
- **Performance**: Reducao de re-renders desnecessarios
- **TypeScript**: Tipos mais precisos para inferencia
- **useFormContext melhorado**: Melhor integracao com contextos aninhados
- **DevTools v4**: Ferramentas de debug atualizadas

---

### 7. Zod v3.25 (Validacao)

Biblioteca de validacao de schemas atualizada:

**Novidades:**
- **Performance**: ~15% mais rapido em validacoes complexas
- **Mensagens de erro**: Mensagens mais claras e customizaveis
- **Coercion melhorada**: `z.coerce.date()` mais robusto
- **Novos metodos**: `z.literal()` e `z.discriminatedUnion()` aprimorados

**Exemplo de uso:**
```typescript
import { z } from 'zod';

const schema = z.object({
  expiresAt: z.coerce.date().min(new Date(), 'Data deve ser futura'),
  quantity: z.coerce.number().positive(),
});
```

---

### 8. Zustand v5.0.10 (State Management)

Gerenciamento de estado global atualizado:

**Novidades:**
- **Persist middleware melhorado**: Migracao de dados mais segura
- **DevTools**: Integracao aprimorada com Redux DevTools
- **TypeScript**: Inferencia de tipos mais precisa
- **Performance**: Reducao de memory leaks

---

### 9. Axios v1.13 (HTTP Client)

Cliente HTTP para requisicoes a API:

**Novidades:**
- **Fetch adapter**: Suporte nativo a Fetch API
- **Retry automatico**: Configuracao mais flexivel de retries
- **Progress tracking**: Melhor acompanhamento de uploads
- **AbortController**: Integracao nativa para cancelamento de requests

---

### 10. Expo SDK 54.0.32 (Mobile Platform)

Plataforma de desenvolvimento mobile:

**Novidades (patches):**
- **Correcoes de bugs**: Diversos fixes de estabilidade
- **iOS 18 compatibility**: Melhor suporte ao iOS 18
- **Android 15**: Compatibilidade com Android 15
- **Expo Notifications**: Correcoes em push notifications

---

### 11. Filament v4.6 (Admin Panel)

O Filament foi atualizado da versao 3.3 para 4.6 usando a ferramenta oficial de upgrade:

**Novidades principais:**
- **Nova arquitetura de Schema**: Metodo `infolist()` agora usa `Schema` em vez de `Infolist`
- **Propriedades com union types**: `$navigationGroup` aceita `UnitEnum | string | null`, `$navigationIcon` aceita `BackedEnum | string | null`
- **Componentes refatorados**: `Filament\Schemas\Components\Section` para sections em infolists
- **Novo sistema de icones**: Usar `<x-filament::icon icon="heroicon-o-name" />` em views Blade
- **Performance melhorada**: Carregamento mais rapido de recursos

**Ferramenta de upgrade utilizada:**
```bash
php artisan filament:upgrade
```

**Documentacao:** [Filament v4 Documentation](https://filamentphp.com/docs/4.x)

---

## Pacotes NAO Atualizados (Breaking Changes)

Os seguintes pacotes possuem versoes mais recentes mas NAO foram atualizados devido a breaking changes significativas:

### Filament v5.x

**Motivo:** Embora ja esteja disponivel, a migracao de v4 para v5 requer mudancas adicionais.

**Mudancas necessarias para futura migracao:**
- Refatoracao adicional de componentes
- Novos padroes de UI

**Recomendacao:** Aguardar maturidade da versao 5 e criar branch dedicada para migracao.

### @hookform/resolvers v5.x

**Motivo:** Requer Zod 4 que ainda esta em beta.

**Quando atualizar:** Quando Zod 4 for lancado como estavel.

### ESLint v9.x

**Motivo:** Nova configuracao "flat config" requer reescrita do `.eslintrc`.

**Quando atualizar:** Quando eslint-config-expo suportar ESLint 9.

---

## Comandos de Verificacao

### Backend
```bash
# Rodar todas as verificacoes
cd web && composer fix

# Apenas testes
cd web && composer t

# Apenas analise estatica
cd web && composer analyse
```

### Mobile
```bash
# Lint
cd mobile && npm run lint

# Type check
cd mobile && npm run check-types

# Testes
cd mobile && npm test
```

---

## Referencias

- [Laravel 12 Release Notes](https://laravel.com/docs/12.x/releases)
- [Filament v4 Documentation](https://filamentphp.com/docs/4.x)
- [Filament v4 Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- [Expo SDK 54 Changelog](https://expo.dev/changelog)
- [React Hook Form v7 Documentation](https://react-hook-form.com/)
- [Zod Documentation](https://zod.dev/)

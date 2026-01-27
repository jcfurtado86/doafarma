# Atualizações Janeiro 2026

Resumo das atualizações de dependências realizadas no projeto DoaFarma.

## Sumário de Versões

### Runtime

| Tecnologia | Versão Anterior | Versão Atual | Notas |
|------------|-----------------|--------------|-------|
| PHP | 8.4 | **8.5 disponível** | Atualização opcional recomendada |

### Backend (Web)

| Pacote | Versão Anterior | Versão Atual |
|--------|-----------------|--------------|
| Filament | v3.3 | v5.1.1 |
| Livewire | v3.7.6 | v4.1.0 |
| Laravel Pint | v1.18 | v1.27 |
| Larastan | v3.0 | v3.9 |
| Scramble | v0.12 | v0.13 |
| Laravel Boost | v1.x | v2.0 |

### Mobile

| Pacote | Versão Anterior | Versão Atual |
|--------|-----------------|--------------|
| Expo SDK | 53 | 54.0.32 |
| React Hook Form | 7.54 | 7.71.0 |
| Zod | 3.23 | 3.25.0 |

---

## PHP 8.5 (Disponível para Atualização)

O PHP 8.5.1 está disponível. O `composer.json` já aceita esta versão (`^8.4`).

### Novidades Principais

#### Pipe Operator (`|>`)
Encadeamento de funções da esquerda para direita, eliminando variáveis intermediárias.

```php
// Antes (PHP 8.4)
$trimmed = trim($title);
$replaced = str_replace(' ', '-', $trimmed);
$slug = strtolower($replaced);

// Agora (PHP 8.5)
$slug = $title
    |> trim(...)
    |> fn($s) => str_replace(' ', '-', $s)
    |> strtolower(...);
```

#### URI Extension Nativa
Parsing robusto de URLs seguindo RFC 3986 e WHATWG URL standards.

```php
use Uri\Rfc3986\Uri;

$uri = new Uri('https://api.doafarma.com/medications?status=available');

$uri->getHost();     // 'api.doafarma.com'
$uri->getPath();     // '/medications'
$uri->getQuery();    // 'status=available'
```

#### Clone With
Atualiza propriedades durante clonagem de objetos.

```php
readonly class Medication
{
    public function __construct(
        public string $name,
        public int $quantity
    ) {}
}

$med = new Medication('Dipirona', 10);
$updated = clone($med, quantity: 5); // Medication com quantity=5
```

#### Atributo #[\NoDiscard]
Avisa quando valores de retorno não são utilizados.

```php
#[\NoDiscard("O resultado deve ser verificado")]
function validateCpf(string $cpf): bool
{
    // ...
}

validateCpf('123.456.789-00'); // Warning: valor de retorno ignorado
```

#### Novas Funções de Array

```php
// Primeiro elemento do array (ou null se vazio)
$first = array_first($medications);

// Último elemento do array (ou null se vazio)
$last = array_last($medications);

// Levenshtein com suporte a graphemes (unicode)
$distance = grapheme_levenshtein('café', 'cafe');
```

#### cURL Persistente
Handles que persistem entre requisições.

```php
$sh = curl_share_init_persistent([
    CURL_LOCK_DATA_DNS,
    CURL_LOCK_DATA_CONNECT,
]);
// Reutiliza conexões entre requisições PHP
```

#### Closures em Expressões Constantes

```php
class MedicationValidator
{
    // Closure como valor padrão de propriedade
    public Closure $formatter = fn($name) => strtoupper($name);

    // Closure em atributo
    #[Validate(fn($v) => $v > 0)]
    public int $quantity;
}
```

### Deprecações Importantes

| Deprecado | Substituto |
|-----------|------------|
| `(integer)` | `(int)` |
| `(boolean)` | `(bool)` |
| `(double)` | `(float)` |
| Backtick operator | `shell_exec()` |
| `__sleep()` / `__wakeup()` | `__serialize()` / `__unserialize()` |
| Semicolon em case | Use colon (`:`) |

### Como Atualizar o PHP

```bash
# Ubuntu/Debian
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.5

# Verificar versão
php -v
```

---

## Filament v5.1.1

### Novidades Principais

#### Integração com IA
- Nova documentação específica para IA e "Filament Blueprint"
- Melhor suporte para desenvolvimento assistido por IA

#### Suporte Aprimorado a Enums
```php
// Agora aceita UnitEnum em authorizeIndividualRecords()
public function authorizeIndividualRecords(): UserRole
{
    return UserRole::Admin;
}
```

#### Requisições Assíncronas
- Implementação de async request handling para melhor performance
- Melhor resposta da interface em operações pesadas

#### Correções de UI/UX
- Posicionamento correto de dropdowns após operações assíncronas
- Correção de z-index em modals com mentions e merge tags
- Cursor de hover correto na navegação lateral

#### Segurança
- Correção de potencial vazamento de dados em exceções de model

### Compatibilidade
- Requer **Livewire v4.0+**
- Requer **PHP 8.2+**
- Requer **Laravel 11.28+**

---

## Livewire v4.1.0

### Novidades Principais

#### Islands (Ilhas)
Regiões isoladas dentro de componentes que atualizam independentemente, melhorando performance sem criar componentes filhos separados.

```php
@island(name: 'stats', lazy: true)
    <div>{{ $this->expensiveStats }}</div>
@endisland
```

**Benefícios:**
- Carregamento lazy de seções pesadas
- Atualização independente de partes do componente
- Melhor performance sem complexidade adicional

#### Single-File Components
Combine PHP e Blade em um único arquivo para componentes simples.

```php
<?php
// resources/views/livewire/counter.blade.php
new class extends Livewire\Volt\Component {
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}
?>

<div>
    <button wire:click="increment">+</button>
    <span>{{ $count }}</span>
</div>
```

#### Novas Diretivas

**wire:sort** - Drag-and-drop nativo
```html
<ul wire:sort="reorder">
    @foreach($items as $item)
        <li wire:sort:item="{{ $item->id }}">{{ $item->name }}</li>
    @endforeach
</ul>
```

**wire:intersect** - Detecção de viewport
```html
<div wire:intersect="loadMore">
    <!-- Carrega mais quando visível -->
</div>
```

**wire:ref** - Referência simples a elementos
```html
<input wire:ref="searchInput" type="text">
```

#### Async Actions
Execute ações sem bloquear outras requisições.

```php
use Livewire\Attributes\Async;

#[Async]
public function processInBackground(): void
{
    // Executa em paralelo, não bloqueia a UI
}
```

Ou via modifier no template:
```html
<button wire:click.async="heavyOperation">Processar</button>
```

#### Performance
- `wire:poll` não bloqueia mais outras requisições
- `wire:model.live` executa requisições em paralelo
- Atualizações de arrays/objetos consolidadas em uma única requisição

#### JavaScript Enhancements
```javascript
// $errors - acesso a erros de validação
$wire.$errors.name // Erros do campo name

// $intercept - modificar requisições
$wire.$intercept(request => {
    request.headers['X-Custom'] = 'value';
});
```

---

## Laravel Pint v1.27

### Novidades Principais

#### Agent Format (v1.27)
Formato especial que ativa automaticamente quando Pint roda via Claude Code ou OpenCode.

#### Suporte a PHP 8.5 (v1.26)
Compatibilidade preliminar com PHP 8.5.

#### Execução Paralela (v1.23+)
```bash
# Formata arquivos em paralelo
./vendor/bin/pint --parallel

# Ou com atalho
./vendor/bin/pint -p
```

#### Entrada via stdin (v1.25+)
```bash
echo "<?php echo 'hello';" | ./vendor/bin/pint --stdin
```

#### Herança de Configuração (v1.23)
```json
{
    "extends": "./base-pint.json",
    "rules": {
        "custom_rule": true
    }
}
```

#### Processos Paralelos Customizáveis (v1.25)
```json
{
    "parallel": {
        "maxProcesses": 4
    }
}
```

---

## Larastan v3.9

### Novidades Principais

#### Cache de Migrations (v3.9)
```neon
parameters:
    enableMigrationCache: true
```
Acelera análise cacheando dados parseados de migrations.

#### Novas Regras de Análise

**NoMissingTranslationsRule** (v3.7)
Detecta chaves de tradução faltantes.

**NoPublicModelScopeAndAccessorRule** (v3.7)
Alerta sobre declarar accessors e scopes como métodos públicos simultaneamente.

**NoUnnecessaryEnumerableToArrayCalls** (v3.6)
Identifica chamadas `.toArray()` redundantes em collections.

**NoAuthFacadeInRequestScopeRule** (v3.6)
Sugere usar métodos do request ao invés de helpers de auth.

**ConfigCollectionRule** (v3.6)
Valida que chaves passadas para `Config::collection()` contêm arrays.

#### Melhorias de Type Inference

```php
// Colunas unsigned agora tipadas como non-negative-int
Schema::create('users', function (Blueprint $table) {
    $table->unsignedInteger('age'); // age: non-negative-int
});

// Melhor detecção de nullability para TEXT e tipos relacionados
$table->text('bio')->nullable(); // bio: string|null
```

#### Suporte a UUIDs/ULIDs
```php
// Inferência correta de tipos para traits
class User extends Model
{
    use HasUuids; // $id inferido como string
}
```

---

## Scramble v0.13

### Novidades Principais

#### Atributo @Endpoint com Título e Descrição
```php
use Dedoc\Scramble\Attributes\Endpoint;

#[Endpoint(
    title: 'Listar Medicamentos',
    description: 'Retorna lista paginada de medicamentos disponíveis'
)]
public function index(): MedicationCollection
{
    return new MedicationCollection(Medication::paginate());
}
```

#### Suporte a Regex Validation
```php
// Agora documentado corretamente no OpenAPI
$request->validate([
    'phone' => 'regex:/^\d{10,11}$/'
]);
```

#### Campos Deprecados
```php
/**
 * @deprecated Use 'full_name' instead
 */
public string $name;
```

#### Customização de Métodos HTTP
Controle granular sobre quais métodos HTTP são documentados para rotas multi-método.

---

## Expo SDK 54

### Novidades Principais

#### Build Pré-compilado para iOS
- Tempo de build limpo reduzido de ~120s para ~10s em M4 Max
- XCFrameworks distribuídos junto com código fonte

#### expo-app-integrity
Verificação de autenticidade do app.

```typescript
import * as AppIntegrity from 'expo-app-integrity';

const result = await AppIntegrity.requestIntegrityToken({
  cloudProjectNumber: 'your-project-number'
});
```

#### expo-glass-effect (iOS 26)
Efeitos Liquid Glass nativos do iOS.

```typescript
import { GlassView } from 'expo-glass-effect';

<GlassView style={styles.container}>
  <Text>Conteúdo com efeito glass</Text>
</GlassView>
```

#### Updates API Melhorada
```typescript
import { useUpdates } from 'expo-updates';

const { downloadProgress } = useUpdates();
// Agora inclui progresso de download de assets

await Updates.reloadAsync({
  reloadScreenOptions: {
    // UI customizada durante reload
  }
});
```

#### expo-sqlite Extensions
```typescript
import * as SQLite from 'expo-sqlite';

await db.loadExtensionAsync('sqlite-vec');
// Suporte a processamento vetorial
```

### Breaking Changes Importantes
- **Legacy Architecture deprecada** - SDK 54 é a última versão com suporte
- **Reanimated v4 requer New Architecture**
- **Xcode mínimo: 16.1**
- **expo-file-system** - Nova API como padrão, legacy em `/legacy`

---

## React Hook Form v7.71

### Novidades Principais

#### FormStateSubscribe Component (v7.68)
Subscrições direcionadas ao estado do form.

```tsx
import { FormStateSubscribe } from 'react-hook-form';

<FormStateSubscribe
  control={control}
  name="email"
  render={({ isDirty, error }) => (
    <span>{isDirty && 'Modificado'}</span>
  )}
/>
```

#### exact Prop para useController (v7.67)
```tsx
const { field } = useController({
  name: 'user.address',
  control,
  exact: true // Subscreve apenas ao campo específico, não ao objeto aninhado
});
```

#### Performance (v7.71)
- **Context Memoization** - FormProvider context memoizado
- **Control Context Separation** - Previne re-renders em cascata
- **Bundle Size** - Redução do tamanho do pacote

---

## Zod v3.25

### Novidades Principais

#### JSON Schema Conversion
```typescript
import { z } from 'zod';

const schema = z.fromJSONSchema({
  type: 'object',
  properties: {
    name: { type: 'string' },
    age: { type: 'integer', minimum: 0 }
  },
  required: ['name']
});
// Suporta draft-2020-12, draft-7, draft-4, e OpenAPI 3.0
```

#### Exclusive Union (z.xor)
```typescript
// Exatamente um schema deve match
const payment = z.xor(
  z.object({ type: z.literal('card'), cardNumber: z.string() }),
  z.object({ type: z.literal('pix'), pixKey: z.string() })
);
```

#### Partial Record (z.looseRecord)
```typescript
// Valida apenas chaves que match, passa outras
const headers = z.looseRecord(
  z.string().startsWith('x-'),
  z.string()
);
```

#### Exact Optional (.exactOptional)
```typescript
const schema = z.object({
  name: z.string(),
  nickname: z.string().exactOptional() // Aceita ausente, mas não undefined explícito
});
```

#### Utility Methods
```typescript
// .apply() para transformações arbitrárias
const schema = z.string().apply(s => s.schema.trim());

// z.slugify() para URLs amigáveis
const slug = z.string().transform(z.slugify());
// "Olá Mundo!" -> "ola-mundo"
```

---

## Como Usar as Novidades

### Livewire Islands no DoaFarma

Use islands para seções pesadas como estatísticas no dashboard:

```php
// Em uma página Filament
@island(name: 'dashboard-stats', lazy: true)
    <x-filament-widgets::stats-overview-widget :widgets="$this->getStatsWidgets()" />
@endisland
```

### Async Actions em Forms

Para operações pesadas como upload de imagens:

```php
use Livewire\Attributes\Async;

#[Async]
public function processImage(): void
{
    // Processa imagem sem bloquear UI
}
```

### Drag-and-drop com wire:sort

Para reordenar listas de medicamentos:

```html
<ul wire:sort="reorderMedications">
    @foreach($medications as $medication)
        <li wire:sort:item="{{ $medication->id }}">
            {{ $medication->name }}
        </li>
    @endforeach
</ul>
```

### Validação com Zod no Mobile

```typescript
import { z } from 'zod';

const medicationSchema = z.object({
  name: z.string().min(3),
  dosage: z.string(),
  expirationDate: z.date().min(new Date()),
  quantity: z.number().positive()
});

// Com JSON Schema para integração com APIs
const apiSchema = z.fromJSONSchema(apiResponseSchema);
```

---

## Referências

- [PHP 8.5 Release](https://www.php.net/releases/8.5/en.php)
- [Filament v5 Upgrade Guide](https://filamentphp.com/docs/5.x/upgrade-guide)
- [Livewire v4 Documentation](https://livewire.laravel.com/docs)
- [Laravel Pint Releases](https://github.com/laravel/pint/releases)
- [Larastan Releases](https://github.com/larastan/larastan/releases)
- [Scramble Releases](https://github.com/dedoc/scramble/releases)
- [Expo SDK 54 Changelog](https://expo.dev/changelog/sdk-54)
- [React Hook Form Releases](https://github.com/react-hook-form/react-hook-form/releases)
- [Zod Releases](https://github.com/colinhacks/zod/releases)

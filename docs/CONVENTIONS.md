# Code Conventions

## PHP / Laravel

### Naming

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `MedicationOffering` |
| Methods | camelCase | `createOffering()` |
| Variables | camelCase | `$medicationOffering` |
| Database tables | snake_case, plural | `medication_offerings` |
| Database columns | snake_case | `expires_at` |
| Routes | kebab-case | `/medication-offerings` |

### File Organization

- **Actions:** One action per file, named `{Verb}{Noun}Action.php`
  - `CreateMedicationOfferingAction.php`
  - `SearchDrugAction.php`
- **Controllers:** Resource controllers when possible
- **Requests:** Named `{Model}{Action}Request.php` or `{Model}Request.php`
- **Resources:** Named `{Model}Resource.php`

### Validation Messages

Write in Portuguese. Define in the request's `messages()` method.

```php
public function messages(): array
{
    return [
        'email.required' => 'O e-mail é obrigatório.',
        'email.unique' => 'Este e-mail já está em uso.',
    ];
}
```

### Type Declarations

Use strict types. Declare return types and parameter types.

```php
public function execute(string $email, string $password): ?User
```

### Formatting

Use Laravel Pint (PSR-12 based). Run `composer pint` before commits.

## TypeScript / React Native

### Naming

| Type | Convention | Example |
|------|------------|---------|
| Components | PascalCase | `MedicationOfferingCard` |
| Functions | camelCase | `fetchOfferings()` |
| Variables | camelCase | `isLoading` |
| Types/Interfaces | PascalCase | `MedicationOffering` |
| Files (components) | PascalCase | `PrimaryButton.tsx` |
| Files (services) | camelCase | `authService.ts` |

### Component Structure

```typescript
// 1. Imports
import { View, Text } from 'react-native';

// 2. Types
interface Props {
    title: string;
    onPress: () => void;
}

// 3. Component
export function MyComponent({ title, onPress }: Props) {
    // 4. Hooks
    const [state, setState] = useState();

    // 5. Handlers
    const handlePress = () => {};

    // 6. Render
    return <View>...</View>;
}
```

### State Management

- Use Zustand for global state
- Use React Hook Form for forms
- Use Zod for validation schemas
- Keep stores focused (one concern per store)

### API Calls

- Centralize in `/services`
- Use the configured Axios instance from `services/api.ts`
- Handle errors in services, not components

## Git

### Commit Messages

Use conventional commits:

```
feat(mobile): add medication offering creation screen
fix(api): handle duplicate email registration error
docs: update API documentation
refactor(web): extract validation to form request
```

### Branches

- `main` - production-ready
- `develop` - integration branch
- `feature/*` - new features
- `fix/*` - bug fixes

## Testing

### Backend (Pest)

- Feature tests for API endpoints
- Unit tests for Actions and Models
- Use factories for test data

### Mobile

- Jest with React Native Testing Library
- Test user interactions, not implementation

## Git Workflow

- main: stable milestones
- develop: active development branch
- feature branches: created per task and merged into develop

All changes are tested locally before merging.

## Activity Logging

### Regra Obrigatória

**Todos os models de domínio importantes DEVEM ter activity logging.**

Isso inclui qualquer model que represente uma entidade de negócio que os administradores precisem auditar.

### Como Adicionar

1. **Adicione a trait `LogsActivity` ao model:**

```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NovoModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['campo1', 'campo2', 'status'])  // Campos a logar
            ->logOnlyDirty()                           // Só loga mudanças reais
            ->dontSubmitEmptyLogs()                    // Não cria log vazio
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Registro foi criado",
                'updated' => "Registro foi atualizado",
                'deleted' => "Registro foi removido",
                default   => "Registro: {$eventName}",
            });
    }
}
```

2. **Atualize o widget de atividades recentes:**

Em `app/Filament/Widgets/RecentActivityWidget.php`, adicione o novo model ao mapeamento de labels.

3. **Crie testes para verificar o logging:**

```php
it('logs when novo model is created', function (): void {
    $model = NovoModel::factory()->create();
    $activity = Activity::where('subject_type', NovoModel::class)
        ->where('subject_id', $model->id)
        ->where('event', 'created')
        ->first();
    expect($activity)->not->toBeNull();
});
```

### O que NÃO Logar

- Campos sensíveis (senhas, tokens, chaves API)
- Models auxiliares ou de infraestrutura
- Operações em batch de alto volume

### Documentação Completa

Veja `docs/ACTIVITY_LOGGING.md` para detalhes sobre arquitetura, consultas úteis e boas práticas.

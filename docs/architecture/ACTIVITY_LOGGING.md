# Activity Logging - Arquitetura e Funcionamento

## Visão Geral

O sistema de activity logging do DoaFarma permite rastrear todas as atividades importantes na plataforma, incluindo criação, atualização e exclusão de entidades, além de ações customizadas como aprovação/rejeição de usuários.

O objetivo é:
- **Auditoria:** Saber quem fez o que e quando
- **Monitoramento:** Detectar comportamentos suspeitos ou mau uso
- **Debugging:** Entender o histórico de mudanças em uma entidade
- **Compliance:** Manter registro de todas as ações para conformidade

## Arquitetura

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FLUXO DE ACTIVITY LOGGING                          │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐      ┌──────────────────┐      ┌──────────────────┐
│     Model        │      │  LogsActivity    │      │  Activity Log    │
│  (User, Offer,   │      │    Trait         │      │    Table         │
│   Request, etc)  │      │                  │      │  (PostgreSQL)    │
└────────┬─────────┘      └────────┬─────────┘      └────────┬─────────┘
         │                         │                         │
         │ 1. create()/update()    │                         │
         │    /delete()            │                         │
         ├────────────────────────>│                         │
         │                         │                         │
         │                         │ 2. Intercepta evento    │
         │                         │    via model events     │
         │                         │                         │
         │                         │ 3. Captura:             │
         │                         │    - subject (model)    │
         │                         │    - causer (auth user) │
         │                         │    - old/new values     │
         │                         │    - event type         │
         │                         │                         │
         │                         │ 4. INSERT activity_log  │
         │                         ├────────────────────────>│
         │                         │                         │
         │                         │                         │
┌────────┴─────────┐      ┌────────┴─────────┐      ┌────────┴─────────┐
│    Filament      │      │ ActivityLog      │      │   Dashboard      │
│    Admin         │<─────│   Resource       │<─────│    Widgets       │
│    Panel         │      │                  │      │                  │
└──────────────────┘      └──────────────────┘      └──────────────────┘
```

## Componentes

### Backend (Laravel)

| Componente | Caminho | Responsabilidade |
|------------|---------|------------------|
| **Spatie Activity Log** | Package via Composer | Biblioteca que gerencia o logging automático |
| **Config** | `config/activitylog.php` | Configurações do pacote (conexão, tabela, etc) |
| **User** | `app/Models/User.php` | Model com `LogsActivity` trait |
| **MedicationOffering** | `app/Models/MedicationOffering.php` | Model com `LogsActivity` trait |
| **MedicationRequest** | `app/Models/MedicationRequest.php` | Model com `LogsActivity` trait |
| **MedicationAppointment** | `app/Models/MedicationAppointment.php` | Model com `LogsActivity` trait |
| **ActivityLogResource** | `app/Filament/Resources/ActivityLogResource.php` | Resource Filament para visualizar logs |
| **ActivityStatsWidget** | `app/Filament/Widgets/ActivityStatsWidget.php` | Stats no dashboard (hoje/semana/mês) |
| **RecentActivityWidget** | `app/Filament/Widgets/RecentActivityWidget.php` | Últimas 10 atividades no dashboard |

### Tabela de Banco de Dados

```sql
CREATE TABLE activity_log (
    id BIGINT PRIMARY KEY,
    log_name VARCHAR(255),
    description TEXT NOT NULL,
    subject_type VARCHAR(255),        -- Ex: App\Models\User
    subject_id BIGINT,                -- ID do modelo afetado
    causer_type VARCHAR(255),         -- Ex: App\Models\User
    causer_id BIGINT,                 -- ID de quem fez a ação
    properties JSON,                  -- old/new values em JSON
    event VARCHAR(255),               -- created, updated, deleted, approved, rejected
    batch_uuid UUID,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_log_name (log_name),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_causer (causer_type, causer_id)
);
```

## Fluxo Detalhado

### 1. Logging Automático (via Trait)

Ao adicionar a trait `LogsActivity` a um model, todas as operações CRUD são automaticamente logadas:

```php
// app/Models/User.php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone_number', 'cpf', 'role', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Usuário {$this->name} foi criado",
                'updated' => "Usuário {$this->name} foi atualizado",
                'deleted' => "Usuário {$this->name} foi removido",
                default   => "Usuário {$this->name}: {$eventName}",
            });
    }
}
```

### 2. Logging Manual (Ações Customizadas)

Para eventos que não são operações CRUD padrão, use o helper `activity()`:

```php
// Exemplo: aprovar um usuário
use Illuminate\Support\Facades\Auth;

$oldStatus = $user->status;
$user->update(['status' => UserStatus::Approved]);

activity()
    ->performedOn($user)                    // Model afetado
    ->causedBy(Auth::user())                // Quem fez
    ->withProperties([
        'old' => ['status' => $oldStatus->value],
        'attributes' => ['status' => UserStatus::Approved->value],
    ])
    ->event('approved')                     // Evento customizado
    ->log("Usuário {$user->name} foi aprovado");
```

### 3. Visualização no Admin

O `ActivityLogResource` permite:
- Listar todas as atividades com filtros
- Filtrar por tipo de entidade (Usuário, Oferta, Solicitação, etc)
- Filtrar por tipo de ação (criado, atualizado, removido, aprovado, rejeitado)
- Filtrar por período
- Ver detalhes: valores antigos e novos em formato JSON

## Tipos de Eventos

### Eventos Automáticos (CRUD)

| Evento | Descrição | Quando Ocorre |
|--------|-----------|---------------|
| `created` | Registro criado | `Model::create()` ou `$model->save()` (novo) |
| `updated` | Registro atualizado | `$model->update()` ou `$model->save()` (existente) |
| `deleted` | Registro removido | `$model->delete()` |

### Eventos Customizados

| Evento | Descrição | Onde é Registrado |
|--------|-----------|-------------------|
| `approved` | Usuário aprovado | `UserResource.php` (ação de aprovação) |
| `rejected` | Usuário rejeitado | `UserResource.php` (ação de rejeição) |

## Como Adicionar a Novos Models

### Passo 1: Adicionar a Trait

```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NovoModel extends Model
{
    use LogsActivity;
}
```

### Passo 2: Configurar Opções

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        // Campos que devem ser logados (exclua senhas, tokens, etc)
        ->logOnly(['campo1', 'campo2', 'status'])
        // Só loga se algo realmente mudou
        ->logOnlyDirty()
        // Não cria log vazio se nada mudou
        ->dontSubmitEmptyLogs()
        // Descrição amigável em português
        ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
            'created' => "Registro {$this->id} foi criado",
            'updated' => "Registro {$this->id} foi atualizado",
            'deleted' => "Registro {$this->id} foi removido",
            default   => "Registro {$this->id}: {$eventName}",
        });
}
```

### Passo 3: Atualizar RecentActivityWidget

Adicione o novo model ao mapeamento de labels em `RecentActivityWidget.php`:

```php
Tables\Columns\TextColumn::make('subject_type')
    ->formatStateUsing(fn (?string $state): string => match ($state) {
        \App\Models\User::class                  => 'Usuário',
        \App\Models\MedicationOffering::class    => 'Oferta',
        \App\Models\MedicationRequest::class     => 'Solicitação',
        \App\Models\MedicationAppointment::class => 'Agendamento',
        \App\Models\NovoModel::class             => 'Novo Model',  // <- Adicione aqui
        default                                  => 'Outro',
    })
```

### Passo 4: Criar Testes

```php
// tests/Feature/ActivityLogTest.php
describe('NovoModel Activity Logging', function (): void {
    it('logs when a novo model is created', function (): void {
        $model = NovoModel::factory()->create();

        $activity = Activity::where('subject_type', NovoModel::class)
            ->where('subject_id', $model->id)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('criado');
    });
});
```

## Configuração

### activitylog.php

```php
return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'delete_records_older_than_days' => 365, // Limpar logs antigos
    'default_log_name' => 'default',
    'default_auth_driver' => null,
    'subject_returns_soft_deleted_models' => false,
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,
    'table_name' => 'activity_log',
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
```

## Boas Práticas

### DO (Faça)

- **Sempre** adicione `LogsActivity` a models de domínio importantes
- **Sempre** defina `logOnly()` para evitar logar dados sensíveis
- **Use** `logOnlyDirty()` para evitar logs desnecessários
- **Crie** eventos customizados para ações de negócio importantes
- **Escreva** descrições em português e amigáveis
- **Teste** que os logs são criados corretamente

### DON'T (Não Faça)

- **Não** logue campos sensíveis (senha, tokens, chaves API)
- **Não** crie logs para operações triviais ou internas
- **Não** esqueça de atualizar widgets ao adicionar novos models
- **Não** dependa apenas dos logs para auditoria crítica (considere também database triggers)

## Consultas Úteis

### Últimas atividades de um usuário específico

```php
Activity::causedBy($user)
    ->latest()
    ->take(10)
    ->get();
```

### Histórico de um model específico

```php
Activity::where('subject_type', User::class)
    ->where('subject_id', $userId)
    ->latest()
    ->get();
```

### Atividades de aprovação no último mês

```php
Activity::where('event', 'approved')
    ->where('created_at', '>=', now()->subMonth())
    ->get();
```

### Atividades por tipo de entidade

```php
Activity::where('subject_type', MedicationOffering::class)
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();
```

## Testes

### Rodar testes de Activity Log

```bash
php artisan test --filter=ActivityLogTest
```

### Testes cobertos

- Logging de criação de usuário
- Logging de atualização de usuário
- Não logar quando campos não mudam
- Logging de criação de MedicationOffering
- Logging de mudança de status de MedicationOffering
- Logging de criação de MedicationRequest
- Logging de mudança de status de MedicationRequest
- Tracking do causer (quem fez a mudança)

## Segurança

- Campos sensíveis (senha, remember_token) são excluídos do logging
- Activity logs são somente leitura no admin (sem edição/deleção)
- Acesso ao ActivityLogResource requer autenticação admin
- Logs são armazenados no banco principal com índices para performance

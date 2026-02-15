# Push Notifications - Arquitetura e Funcionamento

## Visão Geral

O sistema de push notifications do DoaFarma permite enviar lembretes nativos (como WhatsApp, Telegram) para os dispositivos dos usuários sobre seus agendamentos de medicamentos.

## Arquitetura

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FLUXO DE PUSH NOTIFICATIONS                        │
└─────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Mobile     │     │   Backend    │     │  Expo Push   │     │  APNs/FCM    │
│   (Expo)     │     │  (Laravel)   │     │   Service    │     │  (Apple/     │
│              │     │              │     │              │     │   Google)    │
└──────┬───────┘     └──────┬───────┘     └──────┬───────┘     └──────┬───────┘
       │                    │                    │                    │
       │ 1. Login           │                    │                    │
       ├───────────────────>│                    │                    │
       │                    │                    │                    │
       │ 2. Solicita token  │                    │                    │
       │    push (Expo)     │                    │                    │
       ├────────────────────┼───────────────────>│                    │
       │                    │                    │                    │
       │ 3. Retorna token   │                    │                    │
       │    ExponentPush... │                    │                    │
       │<───────────────────┼────────────────────┤                    │
       │                    │                    │                    │
       │ 4. POST /push-token│                    │                    │
       │    (registra)      │                    │                    │
       ├───────────────────>│                    │                    │
       │                    │                    │                    │
       │                    │ 5. Scheduler       │                    │
       │                    │    (cada 5 min)    │                    │
       │                    │    verifica        │                    │
       │                    │    agendamentos    │                    │
       │                    │                    │                    │
       │                    │ 6. Dispara Job     │                    │
       │                    │    p/ lembrete     │                    │
       │                    │                    │                    │
       │                    │ 7. POST /push/send │                    │
       │                    ├───────────────────>│                    │
       │                    │                    │                    │
       │                    │                    │ 8. Entrega via     │
       │                    │                    │    APNs/FCM        │
       │                    │                    ├───────────────────>│
       │                    │                    │                    │
       │ 9. Notificação     │                    │                    │
       │    aparece no      │                    │                    │
       │    dispositivo     │<───────────────────┼────────────────────┤
       │                    │                    │                    │
       └────────────────────┴────────────────────┴────────────────────┘
```

## Componentes

### Backend (Laravel)

| Componente | Caminho | Responsabilidade |
|------------|---------|------------------|
| **PushToken Model** | `app/Models/PushToken.php` | Armazena tokens de push por usuário/dispositivo |
| **RegisterPushTokenAction** | `app/Actions/PushToken/RegisterPushTokenAction.php` | Lógica de registro (upsert) |
| **RegisterController** | `app/Http/Controllers/V1/PushToken/RegisterController.php` | Endpoint POST /push-tokens |
| **DeleteController** | `app/Http/Controllers/V1/PushToken/DeleteController.php` | Endpoint DELETE /push-tokens |
| **SendAppointmentReminderJob** | `app/Jobs/SendAppointmentReminderJob.php` | Envia lembrete de agendamento via Expo API |
| **NotifyDoctorNewRequestJob** | `app/Jobs/NotifyDoctorNewRequestJob.php` | Notifica médico sobre nova solicitação |
| **SendAppointmentRemindersCommand** | `app/Console/Commands/SendAppointmentRemindersCommand.php` | Busca agendamentos e dispara jobs |

### Mobile (React Native/Expo)

| Componente | Caminho | Responsabilidade |
|------------|---------|------------------|
| **pushNotificationService** | `services/pushNotificationService.ts` | Gerencia permissões, tokens e listeners |
| **authStore** | `stores/authStore.ts` | Integra push com login/logout |
| **_layout.tsx** | `app/_layout.tsx` | Configura listeners de notificação |

### Tabela de Banco de Dados

```sql
CREATE TABLE push_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL,           -- ExponentPushToken[xxx]
    device_type VARCHAR(50) NOT NULL,      -- 'ios' ou 'android'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, token)
);
```

## Fluxo Detalhado

### 1. Registro do Token (Login)

Quando o usuário faz login no app:

```typescript
// mobile/stores/authStore.ts
setupPushNotifications: async () => {
  // 1. Solicita permissão e obtém token do Expo
  const expoPushToken = await registerForPushNotificationsAsync();

  if (expoPushToken) {
    // 2. Registra no backend
    await registerPushTokenWithBackend(expoPushToken);

    // 3. Salva localmente
    await AsyncStorage.setItem('push_token', expoPushToken);
  }
}
```

### 2. Scheduler de Lembretes

O Laravel executa a cada 5 minutos:

```php
// routes/console.php
Schedule::command('appointments:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

O comando busca agendamentos que precisam de lembrete:

```php
// SendAppointmentRemindersCommand.php
// Lembrete de 24h: entre 23:55 e 24:05 antes
// Lembrete de 1h: entre 0:55 e 1:05 antes
$appointments = MedicationAppointment::where('status', 'confirmed')
    ->whereBetween('scheduled_date', [$start, $end])
    ->get();
```

### 3. Envio da Notificação

O job faz POST para a API do Expo:

```php
// SendAppointmentReminderJob.php
Http::post('https://exp.host/--/api/v2/push/send', [
    'to' => $pushToken->token,  // ExponentPushToken[xxx]
    'title' => 'Lembrete de Agendamento',
    'body' => 'Você tem um agendamento em 24 horas...',
    'data' => [
        'type' => 'appointment_reminder',
        'appointment_id' => $appointment->id,
    ],
]);
```

### 4. Recebimento no Dispositivo

O app trata notificações de duas formas:

```typescript
// app/_layout.tsx

// Em foreground: mostra Toast
addNotificationReceivedListener((notification) => {
  Toast.show({
    type: 'info',
    text1: notification.request.content.title,
    text2: notification.request.content.body,
  });
});

// Ao tocar: navega para a tela apropriada
addNotificationResponseReceivedListener((response) => {
  const data = response.notification.request.content.data;
  if (data?.type === 'appointment_reminder') {
    // Navega para tela de agendamentos
  }
});
```

## Tipos de Notificações

### 1. Lembrete de Agendamento

| Momento | Janela de Envio | Destinatário | Mensagem |
|---------|-----------------|--------------|----------|
| 24h antes | 23:55 a 24:05 antes do horário | Receptor e Médico | "Você tem um agendamento amanhã às HH:MM..." |
| 1h antes | 0:55 a 1:05 antes do horário | Receptor e Médico | "Você tem um agendamento em 1 hora..." |

**Job:** `SendAppointmentReminderJob`
**Trigger:** Scheduler (a cada 5 minutos)

### 2. Nova Solicitação de Medicamento

| Evento | Destinatário | Mensagem |
|--------|--------------|----------|
| Receptor solicita medicamento | Médico (dono da oferta) | "Nova solicitação de medicamento: [Receptor] solicitou o medicamento [Medicamento]" |

**Job:** `NotifyDoctorNewRequestJob`
**Trigger:** Ao criar um `MedicationRequest` (via `CreateMedicationRequestAction`)

## Testes Manuais

### Pré-requisitos
- Dispositivo físico (emulador não suporta push)
- Build de desenvolvimento do Expo (`npx expo run:android` ou `npx expo run:ios`)

### Passos

1. **Verificar registro do token:**
   ```bash
   # No banco de dados
   SELECT * FROM push_tokens WHERE user_id = <ID_DO_USUARIO>;
   ```

2. **Testar envio manual:**
   ```bash
   # Via Expo Push Tool (https://expo.dev/notifications)
   # Cole o token e envie uma notificação de teste
   ```

3. **Testar lembrete via comando:**
   ```bash
   # Crie um agendamento confirmado para daqui a 24h
   # Execute o comando manualmente
   php artisan appointments:send-reminders
   ```

## Configuração

### app.json (Mobile)
```json
{
  "expo": {
    "plugins": [
      ["expo-notifications", {
        "icon": "./assets/images/notification-icon.png",
        "color": "#7cb518"
      }]
    ],
    "extra": {
      "eas": {
        "projectId": "65356ff8-69a8-4da3-a31c-801ef63120e5"
      }
    }
  }
}
```

### Scheduler (Servidor)
O cron do servidor deve executar o scheduler do Laravel:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Segurança

- Tokens são removidos do backend no logout
- Endpoint de registro requer autenticação (Sanctum)
- Tokens são únicos por usuário/dispositivo (upsert)
- Job verifica se agendamento ainda está 'confirmed' antes de enviar

## Limitações

- Push notifications requerem dispositivo físico
- Expo Push Service tem rate limits (não documentados oficialmente)
- Notificações podem ser bloqueadas pelo sistema operacional se app não for usado

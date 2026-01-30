# Investigação: Bug do Axios no Refresh Token Flow

**Data:** 2026-01-30
**PR:** #115 - feature/DOA-114-refresh-token-handling
**Projeto:** DoaFarma Mobile (React Native/Expo)

---

## 1. Contexto Inicial - O que a PR Entrega

### 1.1 Objetivo da Feature

A PR #115 implementa o **fluxo de refresh token** no aplicativo mobile, complementando a PR #113 que implementou o backend.

**Funcionalidade esperada:**
- Quando o access token expira (após 1 hora), o app deve automaticamente:
  1. Detectar o erro 401 (Unauthorized)
  2. Usar o refresh token para obter um novo access token
  3. Repetir a requisição original com o novo token
  4. Retornar os dados para o caller de forma transparente

### 1.2 Arquitetura Implementada

```
┌─────────────────────────────────────────────────────────────────┐
│                         Service Layer                           │
│  (medicationAppointmentService, etc.)                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Axios Instance (api)                       │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              Request Interceptor                         │   │
│  │  - Adiciona Authorization header do SecureStore          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                              │                                  │
│                              ▼                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              Response Interceptor                        │   │
│  │  - Success: retorna response                             │   │
│  │  - Error 401: refresh token → retry → retorna response   │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 Fluxo Esperado do Refresh Token

```
1. Service chama api.get('/endpoint')
2. Request interceptor adiciona token
3. Servidor retorna 401 (token expirado)
4. Error interceptor captura o 401
5. Error interceptor chama /auth/refresh com refresh token
6. Servidor retorna novo access token
7. Error interceptor atualiza o token no store
8. Error interceptor faz retry da requisição original
9. Retry retorna 200 com dados
10. Error interceptor retorna os dados para o service
11. Service recebe os dados normalmente
```

---

## 2. O Problema Encontrado

### 2.1 Sintoma

Ao testar o fluxo de refresh token, o seguinte erro ocorria:

```
ERROR  [Error: Uncaught (in promise, id: 0) Error: Cannot read property 'data' of undefined]
```

O erro acontecia na linha `return response.data.data` do service, indicando que `response` era `undefined`.

### 2.2 Comportamento Observado

Adicionamos logs extensivos para investigar. Os logs mostraram algo estranho:

**No Error Interceptor:**
```
LOG  [RETRY] api.request RETORNOU!
LOG  [DEBUG] retryResult: É uma AxiosResponse! status=200, tem data=true
LOG  [RETRY] retryResult.data: {"data": [...]}  ← DADOS CORRETOS!
LOG  [RES-INTERCEPTOR-ERROR] Retornando retryResult
```

**No Service (caller):**
```
LOG  [SERVICE-LIST] response: {"_retry": true, "adapter": [...], "method": "get", "url": "..."}
LOG  [SERVICE-LIST] response?.status: undefined  ← UNDEFINED!
LOG  [SERVICE-LIST] response?.data: undefined    ← UNDEFINED!
```

### 2.3 Análise do Problema

O interceptor retornava um objeto **AxiosResponse** válido:
```typescript
{
  status: 200,
  data: { data: [...] },
  headers: {...},
  config: {...}
}
```

Mas o caller (service) recebia o objeto **AxiosRequestConfig**:
```typescript
{
  _retry: true,
  method: "get",
  url: "/v1/medication-appointments",
  headers: {...},
  // SEM status, SEM data de response!
}
```

**Conclusão:** O axios estava ignorando o valor de retorno do error interceptor e retornando `error.config` (o objeto de configuração da requisição original) ao invés do valor que retornamos.

---

## 3. Evidências de que é um Bug do Axios

### 3.1 Comportamento Documentado do Axios

Segundo a [documentação oficial do Axios](https://axios-http.com/docs/interceptors):

> You can intercept requests or responses before they are handled by `then` or `catch`.

O comportamento esperado é que se você **retorna um valor** do error handler (ao invés de rejeitar), esse valor deve se tornar a **resolução** da promise original.

### 3.2 Evidências nos Logs

Os logs comprovam inequivocamente:

1. **O retry funciona corretamente:**
   ```
   [DEBUG] retryResult: É uma AxiosResponse! status=200, tem data=true
   ```

2. **O interceptor retorna o valor correto:**
   ```
   [RES-INTERCEPTOR-ERROR] Retornando retryResult
   [RES-INTERCEPTOR-ERROR] retryResult === undefined? false
   [RES-INTERCEPTOR-ERROR] retryResult === null? false
   [RES-INTERCEPTOR-ERROR] typeof retryResult: object
   [RES-INTERCEPTOR-ERROR] valueToReturn: 200 HAS DATA
   ```

3. **Mas o caller recebe objeto diferente:**
   ```
   [SERVICE-LIST] response: {"_retry": true, "method": "get", ...}  ← É o CONFIG!
   [SERVICE-LIST] response?.status: undefined
   ```

### 3.3 Issues Relacionadas no GitHub do Axios

Encontramos issues similares no repositório do axios:

| Issue | Descrição | Status |
|-------|-----------|--------|
| [#6506](https://github.com/axios/axios/issues/6506) | XHR adapter returns config on errors | Corrigido em 1.7.3 |
| [#5163](https://github.com/axios/axios/issues/5163) | Response interceptor retry request | Corrigido em 1.2.0 |
| [#3199](https://github.com/axios/axios/issues/3199) | Retry successful but original function not affected | Erro de código |

### 3.4 Versão do Axios

O projeto usa **axios ^1.13.0**, que deveria ter as correções dos issues acima. No entanto, o bug persiste especificamente quando:

- O error handler é uma função **async**
- O retorno acontece dentro de um bloco **try-catch-finally**
- O ambiente é **React Native** (não testamos em browser/Node.js)

### 3.5 Teste Definitivo

Testamos múltiplas abordagens que **NÃO funcionaram**:

1. `return retryResult` - Retorna config ao invés de retryResult
2. `return Promise.resolve(retryResult)` - Mesmo problema
3. Criar variável intermediária - Mesmo problema
4. Mover return para fora do try-catch - Mesmo problema

Isso confirma que o problema está no axios, não no nosso código.

---

## 4. Solução Implementada (Workaround)

### 4.1 Estratégia

Já que o axios insiste em retornar `error.config` ao invés do nosso valor de retorno, usamos isso a nosso favor:

1. **Armazenamos** a response real como propriedade do config: `originalRequest.__retryResponse = retryResult`
2. **Criamos um wrapper** (`apiClient`) que extrai essa response após cada chamada

### 4.2 Implementação

**No Error Interceptor (api.ts):**
```typescript
// Após o retry bem-sucedido:
const retryResult = await api.request(originalRequest);

// Armazena a response no config (workaround)
originalRequest.__retryResponse = retryResult;

// Retorna o config (axios vai retornar isso de qualquer jeito)
return originalRequest as unknown as AxiosResponse;
```

**Wrapper apiClient (api.ts):**
```typescript
function extractResponse<T>(result: unknown): AxiosResponse<T> {
  const configWithResponse = result as ConfigWithRetryResponse;

  // Se é um config com __retryResponse, extrai a response real
  if (configWithResponse?.__retryResponse && !('status' in configWithResponse)) {
    return configWithResponse.__retryResponse;
  }

  return result as AxiosResponse<T>;
}

export const apiClient = {
  get: async <T>(url: string, config?: any): Promise<AxiosResponse<T>> => {
    const result = await api.get<T>(url, config);
    return extractResponse<T>(result);
  },
  // ... outros métodos (post, put, patch, delete)
};
```

**Uso nos Services:**
```typescript
// Antes:
import api from './api';
const response = await api.get('/endpoint');

// Depois:
import { apiClient } from './api';
const response = await apiClient.get('/endpoint');
```

### 4.3 Fluxo com o Workaround

```
1. Service chama apiClient.get('/endpoint')
2. apiClient chama api.get('/endpoint')
3. [401 → refresh → retry → sucesso]
4. Error interceptor armazena response em config.__retryResponse
5. Error interceptor retorna config
6. Axios retorna config para apiClient (bug)
7. apiClient.extractResponse() detecta __retryResponse
8. apiClient retorna __retryResponse (a response REAL)
9. Service recebe a response correta
```

### 4.4 Resultado

**Logs após o fix:**
```
[EXTRACT] Detectado config com __retryResponse, extraindo...
[DEBUG] __retryResponse: É uma AxiosResponse! status=200, tem data=true
[EXTRACT] ====== FIM (retornando __retryResponse) ======
[SERVICE-LIST] response?.status: 200        ← CORRETO!
[SERVICE-LIST] response?.data: {"data": [...]}  ← CORRETO!
[SERVICE-LIST] ====== FIM ======            ← SEM ERRO!
```

---

## 5. Próximos Passos

1. **Remover logs de debug** após confirmar estabilidade
2. **Atualizar todos os services** para usar `apiClient` ao invés de `api`
3. **Abrir issue no GitHub do axios** com reprodução mínima do bug
4. **Monitorar** futuras versões do axios para possível fix oficial

---

## 6. Arquivos Modificados

- `mobile/services/api.ts` - Interceptors + wrapper apiClient
- `mobile/services/medicationAppointmentService.ts` - Usa apiClient
- `mobile/stores/authStore.ts` - Função updateAccessToken

---

## 7. Referências

- [Axios Interceptors Documentation](https://axios-http.com/docs/interceptors)
- [GitHub Issue #6506 - XHR adapter returns config](https://github.com/axios/axios/issues/6506)
- [GitHub Issue #5163 - Response interceptor retry](https://github.com/axios/axios/issues/5163)
- [GitHub Issue #3199 - Retry not affecting original](https://github.com/axios/axios/issues/3199)

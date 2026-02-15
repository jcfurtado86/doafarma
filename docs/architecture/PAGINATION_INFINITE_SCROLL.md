# Paginação com Infinite Scroll

Guia técnico para implementar paginação no backend e infinite scroll no mobile.

## Visão Geral

```
┌─────────────────┐         ┌─────────────────┐
│     Mobile      │         │     Backend     │
│                 │         │                 │
│  usePagination  │ ──────► │  ->paginate(N)  │
│       hook      │ page=1  │                 │
│                 │ ◄────── │  JSON response  │
│                 │         │  com meta/links │
│                 │         │                 │
│  onEndReached   │ ──────► │  page=2, 3...   │
│  (scroll 50%)   │         │                 │
└─────────────────┘         └─────────────────┘
```

## Backend (Laravel)

### 1. Modificar a Action

Altere o retorno de `->get()` para `->paginate(N)`:

```php
// ANTES
public function execute(): Collection
{
    return Model::query()
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
}

// DEPOIS
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

private const PER_PAGE = 15;

public function execute(int $perPage = self::PER_PAGE): LengthAwarePaginator
{
    return Model::query()
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}
```

### 2. Resposta JSON automática

O Laravel automaticamente formata a resposta com:

```json
{
  "data": [...],
  "links": {
    "first": "http://api/endpoint?page=1",
    "last": "http://api/endpoint?page=5",
    "prev": null,
    "next": "http://api/endpoint?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### 3. Adicionar índices (se necessário)

Se a query usa `WHERE` ou `ORDER BY` em colunas sem índice, crie uma migration:

```php
// Para WHERE status = 'x' ORDER BY updated_at
Schema::table('tabela', function (Blueprint $table): void {
    $table->index(['status', 'updated_at']);
});

// Para WHERE quantity > 0
Schema::table('tabela', function (Blueprint $table): void {
    $table->index('quantity');
});
```

### 4. Testes

```php
it('retorna resposta paginada', function (): void {
    // Arrange
    Model::factory()->count(20)->create();

    // Act
    $response = $this->getJson('/api/v1/endpoint');

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);
});

it('respeita limite por página', function (): void {
    Model::factory()->count(20)->create();

    $response = $this->getJson('/api/v1/endpoint');

    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('meta.per_page'))->toBe(15);
});

it('retorna página vazia para página inexistente', function (): void {
    Model::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/endpoint?page=999');

    $response->assertOk();
    expect($response->json('data'))->toBeEmpty();
});
```

---

## Mobile (React Native)

### 1. Adicionar método paginado no Service

```typescript
// services/meuService.ts
import { PaginatedResponse } from '@/types/pagination';

export const meuService = {
  // Método original (manter para backward compatibility)
  async getItems(): Promise<Item[]> {
    const response = await apiClient.get<{ data: Item[] }>('/endpoint');
    return response.data.data;
  },

  // Novo método paginado
  async getItemsPaginated(page: number = 1): Promise<PaginatedResponse<Item>> {
    const response = await apiClient.get<PaginatedResponse<Item>>('/endpoint', {
      params: { page },
    });
    return response.data;
  },
};
```

### 2. Usar o hook usePagination

```tsx
import { usePagination } from '@/hooks/usePagination';
import { ListFooterLoader } from '@/components/ListFooterLoader';

export default function MinhaListaScreen() {
  const fetchItemsFn = useCallback(async (page: number) => {
    return meuService.getItemsPaginated(page);
  }, []);

  const {
    items,
    isLoading,
    isLoadingMore,
    error,
    loadMore,
    refresh,
  } = usePagination<Item>({
    fetchFn: fetchItemsFn,
    onError: (err) => console.error(err),
  });

  if (isLoading) {
    return <LoadingScreen />;
  }

  return (
    <FlatList
      data={items}
      keyExtractor={(item) => item.id.toString()}
      renderItem={({ item }) => <ItemCard item={item} />}
      onEndReached={loadMore}
      onEndReachedThreshold={0.5}
      ListFooterComponent={<ListFooterLoader isLoading={isLoadingMore} />}
      refreshing={isLoading}
      onRefresh={refresh}
    />
  );
}
```

### 3. Com dependência de busca/filtro

Se a lista depende de um valor de busca, use `refreshKey`:

```tsx
const [searchQuery, setSearchQuery] = useState('');

const fetchItemsFn = useCallback(async (page: number) => {
  return meuService.searchItemsPaginated(searchQuery, page);
}, [searchQuery]);

const { items, loadMore, refresh } = usePagination<Item>({
  fetchFn: fetchItemsFn,
  refreshKey: searchQuery,  // Recarrega quando searchQuery muda
});
```

---

## Arquivos de Referência

| Arquivo | Descrição |
|---------|-----------|
| `mobile/hooks/usePagination.ts` | Hook genérico para infinite scroll |
| `mobile/types/pagination.ts` | Tipos TypeScript para paginação |
| `mobile/components/ListFooterLoader/` | Componente de loading do rodapé |
| `web/app/Actions/MedicationOffering/SearchMedicationOfferingsAction.php` | Exemplo de Action paginada |

---

## Checklist para Nova Listagem

### Backend
- [ ] Alterar Action para retornar `LengthAwarePaginator`
- [ ] Definir constante `PER_PAGE` (15 para histórico, 50 para busca)
- [ ] Verificar se precisa de índice para a query
- [ ] Adicionar testes de paginação

### Mobile
- [ ] Adicionar método `*Paginated` no service
- [ ] Usar `usePagination` hook na tela
- [ ] Adicionar `onEndReached={loadMore}` no FlatList
- [ ] Adicionar `onEndReachedThreshold={0.5}`
- [ ] Adicionar `ListFooterComponent={<ListFooterLoader />}`
- [ ] Se tem filtro/busca, usar `refreshKey`

---

## Valores Recomendados

| Contexto | Items por página | Threshold |
|----------|------------------|-----------|
| Busca/listagem grande | 50 | 0.5 |
| Histórico/timeline | 15 | 0.5 |
| Chat/mensagens | 20 | 0.3 |

---

## Troubleshooting

### Múltiplas requisições ao scrollar rápido

O hook `usePagination` já protege contra isso usando `isLoadingMoreRef`. Se ainda acontecer, verifique se `onEndReachedThreshold` não está muito alto (máximo 0.5).

### Items duplicados na lista

O hook deduplica automaticamente por `id`. Se seus items não têm `id`, ajuste o hook ou use outro campo único.

### Lista não recarrega quando filtro muda

Use `refreshKey` passando o valor do filtro:

```tsx
usePagination({
  fetchFn: fetchFn,
  refreshKey: searchQuery,  // ou qualquer dependência
});
```

### Performance lenta no backend

1. Verifique se há índice para as colunas usadas em `WHERE` e `ORDER BY`
2. Use `EXPLAIN ANALYZE` no PostgreSQL para ver o plano de execução
3. Considere eager loading para evitar N+1

import { useState, useCallback, useEffect, useRef } from 'react';
import { PaginationMeta } from '@/types/pagination';

interface UsePaginationOptions<T> {
  fetchFn: (page: number) => Promise<{ data: T[]; meta: PaginationMeta }>;
  onError?: (error: string) => void;
  refreshKey?: string | number;
}

interface UsePaginationResult<T> {
  items: T[];
  isLoading: boolean;
  isLoadingMore: boolean;
  hasNextPage: boolean;
  error: string | null;
  currentPage: number;
  totalItems: number;
  loadMore: () => void;
  refresh: () => Promise<void>;
}

export function usePagination<T>({
  fetchFn,
  onError,
  refreshKey = '',
}: UsePaginationOptions<T>): UsePaginationResult<T> {
  const [items, setItems] = useState<T[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const isMounted = useRef(true);
  const isLoadingMoreRef = useRef(false);
  const currentPageRef = useRef(1);
  const fetchFnRef = useRef(fetchFn);
  const onErrorRef = useRef(onError);

  useEffect(() => {
    fetchFnRef.current = fetchFn;
  }, [fetchFn]);

  useEffect(() => {
    onErrorRef.current = onError;
  }, [onError]);

  const fetchPage = useCallback(async (pageNum: number, isRefresh = false) => {
    if (pageNum > 1 && isLoadingMoreRef.current) {
      return;
    }

    if (pageNum === 1) {
      setIsLoading(true);
      if (isRefresh) {
        setItems([]);
      }
    } else {
      setIsLoadingMore(true);
      isLoadingMoreRef.current = true;
    }
    setError(null);

    try {
      const response = await fetchFnRef.current(pageNum);

      if (!isMounted.current) return;

      if (isRefresh || pageNum === 1) {
        setItems(response.data);
      } else {
        setItems((prev) => {
          const existingIds = new Set(prev.map((item: any) => item.id));
          const newItems = response.data.filter((item: any) => !existingIds.has(item.id));
          return [...prev, ...newItems];
        });
      }

      currentPageRef.current = response.meta.current_page;
      setPage(response.meta.current_page);
      setLastPage(response.meta.last_page);
      setTotalItems(response.meta.total);
    } catch (err) {
      if (!isMounted.current) return;

      const errorMessage = err instanceof Error ? err.message : 'Erro ao carregar dados';
      setError(errorMessage);
      onErrorRef.current?.(errorMessage);
    } finally {
      if (isMounted.current) {
        setIsLoading(false);
        setIsLoadingMore(false);
        isLoadingMoreRef.current = false;
      }
    }
  }, []);

  const loadMore = useCallback(() => {
    if (!isLoadingMoreRef.current && !isLoading && currentPageRef.current < lastPage) {
      fetchPage(currentPageRef.current + 1);
    }
  }, [isLoading, lastPage, fetchPage]);

  const refresh = useCallback(async () => {
    currentPageRef.current = 1;
    setPage(1);
    await fetchPage(1, true);
  }, [fetchPage]);

  useEffect(() => {
    isMounted.current = true;
    currentPageRef.current = 1;
    setPage(1);
    fetchPage(1, true);

    return () => {
      isMounted.current = false;
    };
  }, [refreshKey]);

  return {
    items,
    isLoading,
    isLoadingMore,
    hasNextPage: page < lastPage,
    error,
    currentPage: page,
    totalItems,
    loadMore,
    refresh,
  };
}

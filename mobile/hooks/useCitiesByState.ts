import { useEffect, useState } from 'react';

const citiesCache = new Map<string, string[]>();

const IBGE_MUNICIPALITIES_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados';

interface IbgeMunicipality {
  id: number;
  nome: string;
}

export interface UseCitiesByStateResult {
  cities: string[];
  isLoading: boolean;
  error: string | null;
}

export function useCitiesByState(uf: string | null): UseCitiesByStateResult {
  const [cities, setCities] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!uf) {
      setCities([]);
      setError(null);
      return;
    }

    if (citiesCache.has(uf)) {
      setCities(citiesCache.get(uf)!);
      setError(null);
      return;
    }

    let cancelled = false;

    setIsLoading(true);
    setError(null);

    fetch(`${IBGE_MUNICIPALITIES_URL}/${uf}/municipios`)
      .then((res) => {
        if (!res.ok) throw new Error('Resposta inválida da API');
        return res.json() as Promise<IbgeMunicipality[]>;
      })
      .then((data) => {
        if (cancelled) return;
        const names = data.map((m) => m.nome).sort((a, b) => a.localeCompare(b, 'pt-BR'));
        citiesCache.set(uf, names);
        setCities(names);
      })
      .catch(() => {
        if (cancelled) return;
        setError('Erro ao carregar cidades. Tente novamente.');
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [uf]);

  return { cities, isLoading, error };
}

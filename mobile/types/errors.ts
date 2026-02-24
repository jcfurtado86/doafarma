export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

export function isApiError(error: unknown): error is ApiError {
  return (
    typeof error === 'object' &&
    error !== null &&
    'message' in error &&
    typeof (error as Record<string, unknown>).message === 'string'
  );
}

export function getErrorMessage(error: unknown, fallback = 'Ocorreu um erro inesperado'): string {
  if (isApiError(error)) {
    return error.message;
  }
  if (error instanceof Error) {
    return error.message;
  }
  return fallback;
}

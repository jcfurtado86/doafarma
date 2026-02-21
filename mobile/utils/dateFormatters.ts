/**
 * Formats a date string to long format: "20 de fevereiro de 2026"
 */
export function formatLongDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Formats a date string to short format: "20/02/2026"
 */
export function formatShortDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('pt-BR');
}

/**
 * Formats a date string to datetime format: "20/02/2026, 14:30"
 */
export function formatDateTime(dateString: string): string {
  return new Date(dateString).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

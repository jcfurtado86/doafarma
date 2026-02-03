export const formatDateInput = (text: string): string => {
  const numbers = text.replace(/\D/g, '');

  if (numbers.length <= 2) {
    return numbers;
  }

  if (numbers.length <= 4) {
    return `${numbers.slice(0, 2)}/${numbers.slice(2)}`;
  }

  return `${numbers.slice(0, 2)}/${numbers.slice(2, 4)}/${numbers.slice(4, 8)}`;
};

export const convertDateToAPI = (dateString: string): string => {
  const [day, month, year] = dateString.split('/');
  return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
};

export const convertDateFromAPI = (dateString: string): string => {
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
};

export const isDateInPast = (dateStr: string): boolean => {
  // Get today's date in local timezone as YYYY-MM-DD
  const today = new Date();
  const todayYear = today.getFullYear();
  const todayMonth = today.getMonth();
  const todayDay = today.getDate();

  // Parse the date string as local date (not UTC)
  const [year, month, day] = dateStr.split('-').map(Number);

  // Compare dates using numeric values to avoid timezone issues
  if (year < todayYear) return true;
  if (year > todayYear) return false;
  if (month - 1 < todayMonth) return true;
  if (month - 1 > todayMonth) return false;
  return day < todayDay;
};

export const isValidDateFormat = (dateStr: string): boolean => {
  // Validate format first
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return false;
  }

  // Validate that the date is real (e.g., 2026-02-30 is invalid)
  // Use UTC to avoid timezone issues
  const [year, month, day] = dateStr.split('-').map(Number);
  const date = new Date(Date.UTC(year, month - 1, day));

  return (
    date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day
  );
};

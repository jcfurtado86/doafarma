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
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const selectedDate = new Date(dateStr);
  selectedDate.setHours(0, 0, 0, 0);
  return selectedDate < today;
};

export const isValidDateFormat = (dateStr: string): boolean => {
  return /^\d{4}-\d{2}-\d{2}$/.test(dateStr);
};

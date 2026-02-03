export const formatTimeInput = (text: string): string => {
  const numbers = text.replace(/\D/g, '');
  if (numbers.length <= 2) return numbers;
  return `${numbers.slice(0, 2)}:${numbers.slice(2, 4)}`;
};

export const isValidTimeFormat = (timeStr: string): boolean => {
  if (!/^\d{2}:\d{2}$/.test(timeStr)) return false;

  const [hours, minutes] = timeStr.split(':').map(Number);
  return hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59;
};

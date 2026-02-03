import {
  formatDateInput,
  convertDateToAPI,
  convertDateFromAPI,
  isDateInPast,
  isValidDateFormat,
} from '@/utils/validation/dateHelpers';

describe('dateHelpers', () => {
  describe('formatDateInput', () => {
    it('should return numbers only for 2 digits', () => {
      expect(formatDateInput('12')).toBe('12');
    });

    it('should format DD/MM for 4 digits', () => {
      expect(formatDateInput('1203')).toBe('12/03');
    });

    it('should format DD/MM/YYYY for 8 digits', () => {
      expect(formatDateInput('12032024')).toBe('12/03/2024');
    });

    it('should remove non-numeric characters', () => {
      expect(formatDateInput('12/03/2024')).toBe('12/03/2024');
    });
  });

  describe('convertDateToAPI', () => {
    it('should convert DD/MM/YYYY to YYYY-MM-DD', () => {
      expect(convertDateToAPI('12/03/2024')).toBe('2024-03-12');
    });

    it('should pad single digit values', () => {
      expect(convertDateToAPI('1/3/2024')).toBe('2024-03-01');
    });
  });

  describe('convertDateFromAPI', () => {
    it('should convert YYYY-MM-DD to DD/MM/YYYY', () => {
      expect(convertDateFromAPI('2024-03-12')).toBe('12/03/2024');
    });
  });

  describe('isDateInPast', () => {
    it('should return true for past dates', () => {
      expect(isDateInPast('2020-01-01')).toBe(true);
    });

    it('should return false for today', () => {
      // Use local date format to match how isDateInPast works internally
      const today = new Date();
      const year = today.getFullYear();
      const month = String(today.getMonth() + 1).padStart(2, '0');
      const day = String(today.getDate()).padStart(2, '0');
      const todayStr = `${year}-${month}-${day}`;
      expect(isDateInPast(todayStr)).toBe(false);
    });

    it('should return false for future dates', () => {
      expect(isDateInPast('2099-12-31')).toBe(false);
    });
  });

  describe('isValidDateFormat', () => {
    it('should return true for valid format and real date', () => {
      expect(isValidDateFormat('2024-03-12')).toBe(true);
    });

    it('should return false for DD/MM/YYYY format', () => {
      expect(isValidDateFormat('12/03/2024')).toBe(false);
    });

    it('should return false for invalid format', () => {
      expect(isValidDateFormat('invalid')).toBe(false);
    });

    it('should return false for invalid day in month (Feb 30)', () => {
      expect(isValidDateFormat('2026-02-30')).toBe(false);
    });

    it('should return false for invalid month (month 13)', () => {
      expect(isValidDateFormat('2026-13-01')).toBe(false);
    });

    it('should return false for invalid day (day 32)', () => {
      expect(isValidDateFormat('2026-01-32')).toBe(false);
    });

    it('should return true for valid leap year date', () => {
      expect(isValidDateFormat('2024-02-29')).toBe(true); // 2024 is a leap year
    });

    it('should return false for Feb 29 in non-leap year', () => {
      expect(isValidDateFormat('2025-02-29')).toBe(false); // 2025 is not a leap year
    });
  });
});

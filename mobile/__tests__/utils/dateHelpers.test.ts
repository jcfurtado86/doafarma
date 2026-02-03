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
      const today = new Date();
      const todayStr = today.toISOString().split('T')[0];
      expect(isDateInPast(todayStr)).toBe(false);
    });

    it('should return false for future dates', () => {
      expect(isDateInPast('2099-12-31')).toBe(false);
    });
  });

  describe('isValidDateFormat', () => {
    it('should return true for valid format', () => {
      expect(isValidDateFormat('2024-03-12')).toBe(true);
    });

    it('should return false for DD/MM/YYYY format', () => {
      expect(isValidDateFormat('12/03/2024')).toBe(false);
    });

    it('should return false for invalid format', () => {
      expect(isValidDateFormat('invalid')).toBe(false);
    });
  });
});

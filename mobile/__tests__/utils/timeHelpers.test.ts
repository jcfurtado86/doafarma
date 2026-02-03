import { formatTimeInput, isValidTimeFormat } from '@/utils/validation/timeHelpers';

describe('timeHelpers', () => {
  describe('formatTimeInput', () => {
    it('should return numbers only for 2 digits or less', () => {
      expect(formatTimeInput('1')).toBe('1');
      expect(formatTimeInput('12')).toBe('12');
    });

    it('should format HH:MM for more than 2 digits', () => {
      expect(formatTimeInput('123')).toBe('12:3');
      expect(formatTimeInput('1234')).toBe('12:34');
    });

    it('should remove non-numeric characters', () => {
      expect(formatTimeInput('12:34')).toBe('12:34');
    });

    it('should truncate to 4 digits', () => {
      expect(formatTimeInput('12345')).toBe('12:34');
    });
  });

  describe('isValidTimeFormat', () => {
    it('should return true for valid times', () => {
      expect(isValidTimeFormat('00:00')).toBe(true);
      expect(isValidTimeFormat('12:30')).toBe(true);
      expect(isValidTimeFormat('23:59')).toBe(true);
    });

    it('should return false for invalid format', () => {
      expect(isValidTimeFormat('1234')).toBe(false);
      expect(isValidTimeFormat('12')).toBe(false);
    });

    it('should return false for invalid hours', () => {
      expect(isValidTimeFormat('24:00')).toBe(false);
      expect(isValidTimeFormat('25:00')).toBe(false);
    });

    it('should return false for invalid minutes', () => {
      expect(isValidTimeFormat('12:60')).toBe(false);
      expect(isValidTimeFormat('12:99')).toBe(false);
    });
  });
});

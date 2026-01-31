/**
 * Tests for BRAZILIAN_STATES constant
 *
 * Ensures all 27 Brazilian states are present, correctly ordered,
 * and have the expected structure.
 */

import { BRAZILIAN_STATES } from '@/constants/BrazilianStates';

describe('BRAZILIAN_STATES', () => {
  describe('completeness', () => {
    it('should have exactly 27 states', () => {
      expect(BRAZILIAN_STATES).toHaveLength(27);
    });

    it('should include all Brazilian states', () => {
      const expectedStates = [
        'AC',
        'AL',
        'AM',
        'AP',
        'BA',
        'CE',
        'DF',
        'ES',
        'GO',
        'MA',
        'MG',
        'MS',
        'MT',
        'PA',
        'PB',
        'PE',
        'PI',
        'PR',
        'RJ',
        'RN',
        'RO',
        'RR',
        'RS',
        'SC',
        'SE',
        'SP',
        'TO',
      ];

      const stateValues = BRAZILIAN_STATES.map((s) => s.value);
      expect(stateValues).toEqual(expect.arrayContaining(expectedStates));
      expect(expectedStates).toEqual(expect.arrayContaining(stateValues));
    });
  });

  describe('ordering', () => {
    it('should be sorted alphabetically by value', () => {
      const values = BRAZILIAN_STATES.map((s) => s.value);
      const sortedValues = [...values].sort();
      expect(values).toEqual(sortedValues);
    });

    it('should start with AC (Acre)', () => {
      expect(BRAZILIAN_STATES[0].value).toBe('AC');
    });

    it('should end with TO (Tocantins)', () => {
      expect(BRAZILIAN_STATES[26].value).toBe('TO');
    });
  });

  describe('structure', () => {
    it('should have label and value properties for each state', () => {
      BRAZILIAN_STATES.forEach((state) => {
        expect(state).toHaveProperty('label');
        expect(state).toHaveProperty('value');
      });
    });

    it('should have label equal to value (state abbreviation)', () => {
      BRAZILIAN_STATES.forEach((state) => {
        expect(state.label).toBe(state.value);
      });
    });

    it('should have 2-character uppercase values', () => {
      BRAZILIAN_STATES.forEach((state) => {
        expect(state.value).toMatch(/^[A-Z]{2}$/);
      });
    });
  });
});

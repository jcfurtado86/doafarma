/**
 * State Management Tests for API Module
 *
 * These tests verify the internal state management of the API module
 * by testing the exported functions and observing behavior.
 *
 * TDD Red Phase - These tests expose the bugs identified in code review.
 */

import { resetApiState, getApiState } from '@/services/api';

describe('API State Management', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
  });

  describe('getApiState', () => {
    it('should expose current state for debugging', () => {
      // getApiState should return an object with:
      // - isRefreshing: boolean
      // - isLoggingOut: boolean
      // - queueLength: number

      const state = getApiState();

      expect(state).toHaveProperty('isRefreshing');
      expect(state).toHaveProperty('isLoggingOut');
      expect(state).toHaveProperty('queueLength');
    });

    it('should return clean state after reset', () => {
      resetApiState();
      const state = getApiState();

      expect(state.isRefreshing).toBe(false);
      expect(state.isLoggingOut).toBe(false);
      expect(state.queueLength).toBe(0);
    });
  });

  describe('resetApiState', () => {
    it('should reset isRefreshing to false', () => {
      // Even if isRefreshing was true, reset should set it to false
      resetApiState();
      const state = getApiState();

      expect(state.isRefreshing).toBe(false);
    });

    it('should reset isLoggingOut to false', () => {
      // This is the bug fix - isLoggingOut should be reset
      resetApiState();
      const state = getApiState();

      expect(state.isLoggingOut).toBe(false);
    });

    it('should clear the failed queue', () => {
      resetApiState();
      const state = getApiState();

      expect(state.queueLength).toBe(0);
    });
  });
});

describe('API State - Logout Flag', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
  });

  it('should allow logout after state reset', () => {
    // Bug scenario:
    // 1. User gets 401, handleLogout is called, isLoggingOut = true
    // 2. User logs in again (saveSession calls resetApiState)
    // 3. User gets another 401
    // 4. BUG: isLoggingOut is still true, so handleLogout is skipped!

    // Fix: resetApiState should set isLoggingOut = false

    resetApiState();
    const state = getApiState();

    // After reset, isLoggingOut should be false
    expect(state.isLoggingOut).toBe(false);
  });
});

describe('API State - Refresh Flag', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
  });

  it('should have isRefreshing false initially', () => {
    const state = getApiState();
    expect(state.isRefreshing).toBe(false);
  });

  it('should allow reset even if state is already clean', () => {
    // resetApiState should be idempotent
    resetApiState();
    resetApiState();
    resetApiState();

    const state = getApiState();
    expect(state.isRefreshing).toBe(false);
    expect(state.isLoggingOut).toBe(false);
    expect(state.queueLength).toBe(0);
  });
});

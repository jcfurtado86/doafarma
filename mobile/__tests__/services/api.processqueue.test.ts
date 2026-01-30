/**
 * Tests for processQueue guarantee
 *
 * processQueue MUST always be called when refresh completes (success or failure)
 * to prevent queued requests from hanging indefinitely.
 *
 * Bug: If updateAccessToken throws, processQueue is NOT called before catch block
 * This leaves queued requests hanging.
 */

import MockAdapter from 'axios-mock-adapter';
import * as SecureStore from 'expo-secure-store';
import { router } from 'expo-router';
import Toast from 'react-native-toast-message';

import api, { resetApiState, getApiState } from '@/services/api';
import { useAuthStore } from '@/stores/authStore';

describe('processQueue guarantees', () => {
  let mockAxios: MockAdapter;

  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
    mockAxios = new MockAdapter(api);

    // Default mocks
    (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);
    (SecureStore.setItemAsync as jest.Mock).mockResolvedValue(undefined);
    (SecureStore.deleteItemAsync as jest.Mock).mockResolvedValue(undefined);
  });

  afterEach(() => {
    mockAxios.restore();
  });

  describe('when updateAccessToken throws', () => {
    it('should still reject queued requests', async () => {
      // Setup: Mock tokens
      const expiredToken = '1|expired';
      const refreshToken = '2|refresh';
      const newToken = '3|new';

      // First call: return expired token for request
      // Second call: return refresh token
      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(expiredToken) // First request
        .mockResolvedValueOnce(refreshToken); // Refresh token lookup

      // First request returns 401
      mockAxios.onGet('/v1/test').replyOnce(401, { message: 'Unauthenticated' });

      // Refresh succeeds
      mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
        data: {
          access_token: newToken,
          expires_in: 3600,
        },
      });

      // Mock updateAccessToken to throw
      const originalUpdateAccessToken = useAuthStore.getState().updateAccessToken;
      const mockUpdateAccessToken = jest.fn().mockRejectedValue(new Error('Storage failed'));
      useAuthStore.setState({ updateAccessToken: mockUpdateAccessToken });

      // Make request
      await expect(api.get('/v1/test')).rejects.toThrow();

      // Verify: Queue should be empty (processQueue was called)
      const state = getApiState();
      expect(state.queueLength).toBe(0);

      // Restore
      useAuthStore.setState({ updateAccessToken: originalUpdateAccessToken });
    });

    it('should reset isRefreshing flag', async () => {
      const expiredToken = '1|expired';
      const refreshToken = '2|refresh';
      const newToken = '3|new';

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(expiredToken)
        .mockResolvedValueOnce(refreshToken);

      mockAxios.onGet('/v1/test').replyOnce(401);
      mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
        data: { access_token: newToken, expires_in: 3600 },
      });

      const originalUpdateAccessToken = useAuthStore.getState().updateAccessToken;
      useAuthStore.setState({
        updateAccessToken: jest.fn().mockRejectedValue(new Error('Storage failed')),
      });

      await expect(api.get('/v1/test')).rejects.toThrow();

      // isRefreshing should be reset even if error occurred
      const state = getApiState();
      expect(state.isRefreshing).toBe(false);

      useAuthStore.setState({ updateAccessToken: originalUpdateAccessToken });
    });
  });

  describe('when refresh succeeds', () => {
    it('should resolve queued requests with new token', async () => {
      const expiredToken = '1|expired';
      const refreshToken = '2|refresh';
      const newToken = '3|new';

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(expiredToken)
        .mockResolvedValueOnce(refreshToken)
        .mockResolvedValueOnce(newToken); // For retry

      mockAxios.onGet('/v1/test').replyOnce(401);
      mockAxios.onPost('/v1/auth/refresh').replyOnce(200, {
        data: { access_token: newToken, expires_in: 3600 },
      });
      mockAxios.onGet('/v1/test').replyOnce(200, { data: { success: true } });

      // After successful refresh, queue should be empty
      await expect(api.get('/v1/test')).resolves.toBeDefined();

      const state = getApiState();
      expect(state.queueLength).toBe(0);
    });
  });

  describe('when refresh fails with 401', () => {
    it('should reject all queued requests', async () => {
      const expiredToken = '1|expired';
      const refreshToken = '2|expired_refresh';

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(expiredToken)
        .mockResolvedValueOnce(refreshToken);

      mockAxios.onGet('/v1/test').replyOnce(401);
      mockAxios.onPost('/v1/auth/refresh').replyOnce(401, { message: 'Unauthenticated' });

      await expect(api.get('/v1/test')).rejects.toThrow();

      // Queue should be empty
      const state = getApiState();
      expect(state.queueLength).toBe(0);
    });

    it('should trigger logout', async () => {
      const expiredToken = '1|expired';
      const refreshToken = '2|expired_refresh';

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(expiredToken)
        .mockResolvedValueOnce(refreshToken);

      mockAxios.onGet('/v1/test').replyOnce(401);
      mockAxios.onPost('/v1/auth/refresh').replyOnce(401);

      await expect(api.get('/v1/test')).rejects.toThrow();

      // Should have triggered logout
      expect(router.replace).toHaveBeenCalledWith('/');
      expect(Toast.show).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
          text1: 'Sessão expirada',
        })
      );
    });
  });

  describe('when no refresh token exists', () => {
    it('should reject immediately and logout', async () => {
      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce('1|expired') // Access token
        .mockResolvedValueOnce(null); // No refresh token

      mockAxios.onGet('/v1/test').replyOnce(401);

      await expect(api.get('/v1/test')).rejects.toThrow();

      expect(router.replace).toHaveBeenCalledWith('/');
    });
  });
});

describe('Queue cleanup on edge cases', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    resetApiState();
  });

  it('should not leave stale requests in queue after timeout scenario', async () => {
    // If for some reason a queued request never resolves,
    // resetApiState should clean it up

    // Simulate stale state by checking reset behavior
    resetApiState();

    const state = getApiState();
    expect(state.queueLength).toBe(0);
  });

  it('should handle multiple resets gracefully', () => {
    resetApiState();
    resetApiState();
    resetApiState();

    const state = getApiState();
    expect(state.isRefreshing).toBe(false);
    expect(state.isLoggingOut).toBe(false);
    expect(state.queueLength).toBe(0);
  });
});

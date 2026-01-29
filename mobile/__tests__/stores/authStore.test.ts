/**
 * Tests for authStore - Refresh Token Implementation
 *
 * TDD Red Phase - These tests should FAIL until implementation is complete.
 *
 * Test Coverage:
 * - saveSession: saves both access and refresh tokens
 * - updateAccessToken: updates only access token after refresh
 * - getRefreshToken: retrieves refresh token from SecureStore
 * - logout: removes both tokens
 * - checkAuth: restores both tokens from storage
 */

import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useAuthStore } from '@/stores/authStore';
import api from '@/services/api';

// Reset store state before each test
beforeEach(() => {
  useAuthStore.setState({
    user: null,
    token: null,
    refreshToken: null,
    expiresAt: null,
    pushToken: null,
    isAuthenticated: false,
    isLoading: false,
    error: null,
  });

  jest.clearAllMocks();
});

describe('authStore', () => {
  // ============================================
  // INITIAL STATE
  // ============================================
  describe('initial state', () => {
    it('should have refreshToken as null initially', () => {
      const state = useAuthStore.getState();
      expect(state.refreshToken).toBeNull();
    });

    it('should have expiresAt as null initially', () => {
      const state = useAuthStore.getState();
      expect(state.expiresAt).toBeNull();
    });
  });

  // ============================================
  // SAVE SESSION
  // ============================================
  describe('saveSession', () => {
    const mockUser = {
      id: 1,
      name: 'Dr. João Silva',
      email: 'joao@example.com',
      role: 'doctor' as const,
    };
    const mockAccessToken = '1|access_token_abc123';
    const mockRefreshToken = '2|refresh_token_xyz789';
    const mockExpiresIn = 3600; // 1 hour in seconds

    it('should save access token to SecureStore', async () => {
      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      expect(SecureStore.setItemAsync).toHaveBeenCalledWith('auth_token', mockAccessToken);
    });

    it('should save refresh token to SecureStore', async () => {
      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      expect(SecureStore.setItemAsync).toHaveBeenCalledWith('refresh_token', mockRefreshToken);
    });

    it('should save user to AsyncStorage', async () => {
      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      expect(AsyncStorage.setItem).toHaveBeenCalledWith('user', JSON.stringify(mockUser));
    });

    it('should update state with user and tokens', async () => {
      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
      expect(state.token).toBe(mockAccessToken);
      expect(state.refreshToken).toBe(mockRefreshToken);
      expect(state.isAuthenticated).toBe(true);
    });

    it('should calculate and save expiresAt timestamp', async () => {
      const beforeCall = Date.now();

      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      const afterCall = Date.now();
      const state = useAuthStore.getState();

      // expiresAt should be approximately now + expiresIn seconds
      const expectedMinExpiresAt = beforeCall + mockExpiresIn * 1000;
      const expectedMaxExpiresAt = afterCall + mockExpiresIn * 1000;

      expect(state.expiresAt).toBeGreaterThanOrEqual(expectedMinExpiresAt);
      expect(state.expiresAt).toBeLessThanOrEqual(expectedMaxExpiresAt);
    });

    it('should set Authorization header on api instance', async () => {
      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      expect(api.defaults.headers.common['Authorization']).toBe(`Bearer ${mockAccessToken}`);
    });

    it('should handle storage errors gracefully', async () => {
      (SecureStore.setItemAsync as jest.Mock).mockRejectedValueOnce(new Error('Storage error'));

      await useAuthStore
        .getState()
        .saveSession(mockUser, mockAccessToken, mockRefreshToken, mockExpiresIn);

      const state = useAuthStore.getState();
      expect(state.error).toBe('Failed to save session');
    });
  });

  // ============================================
  // UPDATE ACCESS TOKEN
  // ============================================
  describe('updateAccessToken', () => {
    const initialUser = {
      id: 1,
      name: 'Dr. João Silva',
      email: 'joao@example.com',
      role: 'doctor' as const,
    };
    const initialAccessToken = '1|old_access_token';
    const initialRefreshToken = '2|refresh_token';
    const newAccessToken = '3|new_access_token';
    const newExpiresIn = 3600;

    beforeEach(async () => {
      // Setup initial authenticated state
      (SecureStore.setItemAsync as jest.Mock).mockResolvedValue(undefined);
      (AsyncStorage.setItem as jest.Mock).mockResolvedValue(undefined);

      useAuthStore.setState({
        user: initialUser,
        token: initialAccessToken,
        refreshToken: initialRefreshToken,
        expiresAt: Date.now() + 100000,
        isAuthenticated: true,
        isLoading: false,
        error: null,
        pushToken: null,
      });
    });

    it('should update access token in SecureStore', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      expect(SecureStore.setItemAsync).toHaveBeenCalledWith('auth_token', newAccessToken);
    });

    it('should NOT update refresh token', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      // Should not call setItemAsync for refresh_token
      expect(SecureStore.setItemAsync).not.toHaveBeenCalledWith(
        'refresh_token',
        expect.any(String)
      );
    });

    it('should update token in state', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      const state = useAuthStore.getState();
      expect(state.token).toBe(newAccessToken);
    });

    it('should keep refreshToken unchanged in state', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      const state = useAuthStore.getState();
      expect(state.refreshToken).toBe(initialRefreshToken);
    });

    it('should update expiresAt timestamp', async () => {
      const beforeCall = Date.now();

      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      const afterCall = Date.now();
      const state = useAuthStore.getState();

      const expectedMinExpiresAt = beforeCall + newExpiresIn * 1000;
      const expectedMaxExpiresAt = afterCall + newExpiresIn * 1000;

      expect(state.expiresAt).toBeGreaterThanOrEqual(expectedMinExpiresAt);
      expect(state.expiresAt).toBeLessThanOrEqual(expectedMaxExpiresAt);
    });

    it('should update Authorization header on api instance', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      expect(api.defaults.headers.common['Authorization']).toBe(`Bearer ${newAccessToken}`);
    });

    it('should keep user unchanged', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      const state = useAuthStore.getState();
      expect(state.user).toEqual(initialUser);
    });

    it('should keep isAuthenticated as true', async () => {
      await useAuthStore.getState().updateAccessToken(newAccessToken, newExpiresIn);

      const state = useAuthStore.getState();
      expect(state.isAuthenticated).toBe(true);
    });
  });

  // ============================================
  // GET REFRESH TOKEN
  // ============================================
  describe('getRefreshToken', () => {
    it('should return refresh token from SecureStore', async () => {
      const mockRefreshToken = '2|refresh_token_xyz';
      (SecureStore.getItemAsync as jest.Mock).mockResolvedValueOnce(mockRefreshToken);

      const result = await useAuthStore.getState().getRefreshToken();

      expect(SecureStore.getItemAsync).toHaveBeenCalledWith('refresh_token');
      expect(result).toBe(mockRefreshToken);
    });

    it('should return null when no refresh token exists', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockResolvedValueOnce(null);

      const result = await useAuthStore.getState().getRefreshToken();

      expect(result).toBeNull();
    });

    it('should return null on storage error', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockRejectedValueOnce(new Error('Storage error'));

      const result = await useAuthStore.getState().getRefreshToken();

      expect(result).toBeNull();
    });
  });

  // ============================================
  // LOGOUT
  // ============================================
  describe('logout', () => {
    beforeEach(() => {
      // Setup authenticated state
      useAuthStore.setState({
        user: { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' },
        token: '1|access_token',
        refreshToken: '2|refresh_token',
        expiresAt: Date.now() + 3600000,
        isAuthenticated: true,
        isLoading: false,
        error: null,
        pushToken: null,
      });
    });

    it('should delete access token from SecureStore', async () => {
      await useAuthStore.getState().logout();

      expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('auth_token');
    });

    it('should delete refresh token from SecureStore', async () => {
      await useAuthStore.getState().logout();

      expect(SecureStore.deleteItemAsync).toHaveBeenCalledWith('refresh_token');
    });

    it('should remove user from AsyncStorage', async () => {
      await useAuthStore.getState().logout();

      expect(AsyncStorage.removeItem).toHaveBeenCalledWith('user');
    });

    it('should clear token state', async () => {
      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.token).toBeNull();
    });

    it('should clear refreshToken state', async () => {
      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.refreshToken).toBeNull();
    });

    it('should clear expiresAt state', async () => {
      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.expiresAt).toBeNull();
    });

    it('should clear user state', async () => {
      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.user).toBeNull();
    });

    it('should set isAuthenticated to false', async () => {
      await useAuthStore.getState().logout();

      const state = useAuthStore.getState();
      expect(state.isAuthenticated).toBe(false);
    });

    it('should remove Authorization header from api', async () => {
      api.defaults.headers.common['Authorization'] = 'Bearer some_token';

      await useAuthStore.getState().logout();

      expect(api.defaults.headers.common['Authorization']).toBeUndefined();
    });
  });

  // ============================================
  // CHECK AUTH
  // ============================================
  describe('checkAuth', () => {
    it('should return false when no access token in storage', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);

      const result = await useAuthStore.getState().checkAuth();

      expect(result).toBe(false);
    });

    it('should return false when no user in storage', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockResolvedValue('1|token');
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(null);

      const result = await useAuthStore.getState().checkAuth();

      expect(result).toBe(false);
    });

    it('should restore access token from SecureStore', async () => {
      const mockToken = '1|access_token';
      const mockRefreshToken = '2|refresh_token';
      const mockUser = { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' };

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(mockToken) // auth_token
        .mockResolvedValueOnce(mockRefreshToken); // refresh_token
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(JSON.stringify(mockUser));

      await useAuthStore.getState().checkAuth();

      const state = useAuthStore.getState();
      expect(state.token).toBe(mockToken);
    });

    it('should restore refresh token from SecureStore', async () => {
      const mockToken = '1|access_token';
      const mockRefreshToken = '2|refresh_token';
      const mockUser = { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' };

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(mockToken) // auth_token
        .mockResolvedValueOnce(mockRefreshToken); // refresh_token
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(JSON.stringify(mockUser));

      await useAuthStore.getState().checkAuth();

      const state = useAuthStore.getState();
      expect(state.refreshToken).toBe(mockRefreshToken);
    });

    it('should restore user from AsyncStorage', async () => {
      const mockToken = '1|access_token';
      const mockRefreshToken = '2|refresh_token';
      const mockUser = { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' };

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(mockToken)
        .mockResolvedValueOnce(mockRefreshToken);
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(JSON.stringify(mockUser));

      await useAuthStore.getState().checkAuth();

      const state = useAuthStore.getState();
      expect(state.user).toEqual(mockUser);
    });

    it('should set isAuthenticated to true when tokens exist', async () => {
      const mockToken = '1|access_token';
      const mockRefreshToken = '2|refresh_token';
      const mockUser = { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' };

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(mockToken)
        .mockResolvedValueOnce(mockRefreshToken);
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(JSON.stringify(mockUser));

      const result = await useAuthStore.getState().checkAuth();

      expect(result).toBe(true);
      expect(useAuthStore.getState().isAuthenticated).toBe(true);
    });

    it('should set Authorization header on api', async () => {
      const mockToken = '1|access_token';
      const mockRefreshToken = '2|refresh_token';
      const mockUser = { id: 1, name: 'Test', email: 'test@test.com', role: 'doctor' };

      (SecureStore.getItemAsync as jest.Mock)
        .mockResolvedValueOnce(mockToken)
        .mockResolvedValueOnce(mockRefreshToken);
      (AsyncStorage.getItem as jest.Mock).mockResolvedValue(JSON.stringify(mockUser));

      await useAuthStore.getState().checkAuth();

      expect(api.defaults.headers.common['Authorization']).toBe(`Bearer ${mockToken}`);
    });

    it('should set isLoading to false after check completes', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockResolvedValue(null);

      await useAuthStore.getState().checkAuth();

      expect(useAuthStore.getState().isLoading).toBe(false);
    });

    it('should handle storage errors gracefully', async () => {
      (SecureStore.getItemAsync as jest.Mock).mockRejectedValue(new Error('Storage error'));

      const result = await useAuthStore.getState().checkAuth();

      expect(result).toBe(false);
      expect(useAuthStore.getState().isLoading).toBe(false);
    });
  });
});

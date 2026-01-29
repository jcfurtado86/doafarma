/**
 * Tests for authService - Refresh Token Implementation
 *
 * TDD Red Phase - These tests should FAIL until implementation is complete.
 *
 * Test Coverage:
 * - refreshToken: calls correct endpoint with refresh token
 * - refreshToken: returns new access token on success
 * - refreshToken: handles errors appropriately
 * - login: returns both access and refresh tokens
 */

import axios from 'axios';
import api from '@/services/api';
import { authService, AuthServiceError } from '@/services/authService';
import { API_ENDPOINTS } from '@/config/endpoints';

// Mock the api module
jest.mock('@/services/api', () => ({
  __esModule: true,
  default: {
    post: jest.fn(),
    defaults: {
      headers: {
        common: {},
      },
    },
  },
}));

beforeEach(() => {
  jest.clearAllMocks();
});

describe('authService', () => {
  // ============================================
  // REFRESH TOKEN
  // ============================================
  describe('refreshToken', () => {
    const mockRefreshToken = '2|refresh_token_xyz789';
    const mockNewAccessToken = '3|new_access_token_abc123';
    const mockExpiresIn = 3600;

    it('should call POST /v1/auth/refresh endpoint', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            access_token: mockNewAccessToken,
            expires_in: mockExpiresIn,
          },
          message: 'Token renovado com sucesso',
        },
      });

      await authService.refreshToken(mockRefreshToken);

      expect(api.post).toHaveBeenCalledWith(
        '/v1/auth/refresh',
        {},
        {
          headers: {
            Authorization: `Bearer ${mockRefreshToken}`,
          },
        }
      );
    });

    it('should return new access token on success', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            access_token: mockNewAccessToken,
            expires_in: mockExpiresIn,
          },
          message: 'Token renovado com sucesso',
        },
      });

      const result = await authService.refreshToken(mockRefreshToken);

      expect(result.data.access_token).toBe(mockNewAccessToken);
    });

    it('should return expires_in on success', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            access_token: mockNewAccessToken,
            expires_in: mockExpiresIn,
          },
          message: 'Token renovado com sucesso',
        },
      });

      const result = await authService.refreshToken(mockRefreshToken);

      expect(result.data.expires_in).toBe(mockExpiresIn);
    });

    it('should throw AuthServiceError on 401 (refresh token expired)', async () => {
      const axiosError = {
        isAxiosError: true,
        response: {
          status: 401,
          data: { message: 'Unauthenticated' },
        },
      };
      (api.post as jest.Mock).mockRejectedValueOnce(axiosError);

      // Mock axios.isAxiosError to return true for our error
      jest.spyOn(axios, 'isAxiosError').mockReturnValue(true);

      await expect(authService.refreshToken(mockRefreshToken)).rejects.toThrow(AuthServiceError);
    });

    it('should throw AuthServiceError with correct statusCode on 401', async () => {
      const axiosError = {
        isAxiosError: true,
        response: {
          status: 401,
          data: { message: 'Unauthenticated' },
        },
      };
      (api.post as jest.Mock).mockRejectedValueOnce(axiosError);
      jest.spyOn(axios, 'isAxiosError').mockReturnValue(true);

      try {
        await authService.refreshToken(mockRefreshToken);
        fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(AuthServiceError);
        expect((error as AuthServiceError).statusCode).toBe(401);
      }
    });

    it('should throw AuthServiceError on network error', async () => {
      const networkError = {
        isAxiosError: true,
        response: undefined,
        message: 'Network Error',
      };
      (api.post as jest.Mock).mockRejectedValueOnce(networkError);
      jest.spyOn(axios, 'isAxiosError').mockReturnValue(true);

      try {
        await authService.refreshToken(mockRefreshToken);
        fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(AuthServiceError);
        expect((error as AuthServiceError).isNetworkError).toBe(true);
      }
    });

    it('should throw AuthServiceError on server error (500)', async () => {
      const serverError = {
        isAxiosError: true,
        response: {
          status: 500,
          data: { message: 'Internal Server Error' },
        },
      };
      (api.post as jest.Mock).mockRejectedValueOnce(serverError);
      jest.spyOn(axios, 'isAxiosError').mockReturnValue(true);

      try {
        await authService.refreshToken(mockRefreshToken);
        fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(AuthServiceError);
        expect((error as AuthServiceError).isServerError).toBe(true);
        expect((error as AuthServiceError).statusCode).toBe(500);
      }
    });

    it('should throw AuthServiceError on 403 (user not approved)', async () => {
      const forbiddenError = {
        isAxiosError: true,
        response: {
          status: 403,
          data: { message: 'Your account is pending approval' },
        },
      };
      (api.post as jest.Mock).mockRejectedValueOnce(forbiddenError);
      jest.spyOn(axios, 'isAxiosError').mockReturnValue(true);

      try {
        await authService.refreshToken(mockRefreshToken);
        fail('Should have thrown an error');
      } catch (error) {
        expect(error).toBeInstanceOf(AuthServiceError);
        expect((error as AuthServiceError).statusCode).toBe(403);
      }
    });
  });

  // ============================================
  // LOGIN (Updated interface)
  // ============================================
  describe('login', () => {
    const mockCredentials = {
      email: 'doctor@example.com',
      password: 'password123',
      device_name: 'iPhone 15',
    };

    const mockUser = {
      id: 1,
      name: 'Dr. João Silva',
      email: 'doctor@example.com',
      role: 'doctor' as const,
    };

    it('should return access_token from login response', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            user: mockUser,
            access_token: '1|access_token_abc',
            refresh_token: '2|refresh_token_xyz',
            expires_in: 3600,
            refresh_expires_in: 2592000,
          },
          message: 'Login realizado com sucesso',
        },
      });

      const result = await authService.login(mockCredentials);

      expect(result.data.access_token).toBe('1|access_token_abc');
    });

    it('should return refresh_token from login response', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            user: mockUser,
            access_token: '1|access_token_abc',
            refresh_token: '2|refresh_token_xyz',
            expires_in: 3600,
            refresh_expires_in: 2592000,
          },
          message: 'Login realizado com sucesso',
        },
      });

      const result = await authService.login(mockCredentials);

      expect(result.data.refresh_token).toBe('2|refresh_token_xyz');
    });

    it('should return expires_in from login response', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            user: mockUser,
            access_token: '1|access_token_abc',
            refresh_token: '2|refresh_token_xyz',
            expires_in: 3600,
            refresh_expires_in: 2592000,
          },
          message: 'Login realizado com sucesso',
        },
      });

      const result = await authService.login(mockCredentials);

      expect(result.data.expires_in).toBe(3600);
    });

    it('should return refresh_expires_in from login response', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            user: mockUser,
            access_token: '1|access_token_abc',
            refresh_token: '2|refresh_token_xyz',
            expires_in: 3600,
            refresh_expires_in: 2592000,
          },
          message: 'Login realizado com sucesso',
        },
      });

      const result = await authService.login(mockCredentials);

      expect(result.data.refresh_expires_in).toBe(2592000);
    });

    it('should return user from login response', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({
        data: {
          data: {
            user: mockUser,
            access_token: '1|access_token_abc',
            refresh_token: '2|refresh_token_xyz',
            expires_in: 3600,
            refresh_expires_in: 2592000,
          },
          message: 'Login realizado com sucesso',
        },
      });

      const result = await authService.login(mockCredentials);

      expect(result.data.user).toEqual(mockUser);
    });
  });

  // ============================================
  // LOGOUT
  // ============================================
  describe('logout', () => {
    it('should call POST /logout endpoint', async () => {
      (api.post as jest.Mock).mockResolvedValueOnce({ data: {} });

      await authService.logout();

      expect(api.post).toHaveBeenCalledWith(API_ENDPOINTS.AUTH.LOGOUT);
    });

    it('should not throw even if backend returns error', async () => {
      (api.post as jest.Mock).mockRejectedValueOnce(new Error('Network error'));

      // Should not throw
      await expect(authService.logout()).resolves.not.toThrow();
    });
  });
});

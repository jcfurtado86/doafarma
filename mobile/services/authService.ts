import api from './api';
import axios, { AxiosError } from 'axios';
import { User } from '@/stores/authStore';
import { API_ENDPOINTS } from '@/config/endpoints';
import { getErrorMessage } from '@/utils/error';

export interface LoginCredentials {
  email: string;
  password: string;
  device_name: string;
}

export interface LoginResponse {
  data: {
    user: User;
    access_token: string;
    refresh_token: string;
    expires_in: number;
    refresh_expires_in: number;
  };
  message: string;
}

export interface RefreshTokenResponse {
  data: {
    access_token: string;
    expires_in: number;
  };
  message: string;
}

export interface ApiValidationError {
  message: string;
  errors: Record<string, string[]>;
}

export class AuthServiceError extends Error {
  isValidationError: boolean;
  isNetworkError: boolean;
  isServerError: boolean;
  isRateLimited: boolean;
  validationErrors: Record<string, string[]> | null;
  statusCode: number | null;

  constructor(
    message: string,
    options: {
      isValidationError?: boolean;
      isNetworkError?: boolean;
      isServerError?: boolean;
      isRateLimited?: boolean;
      validationErrors?: Record<string, string[]> | null;
      statusCode?: number | null;
    } = {}
  ) {
    super(message);
    this.name = 'AuthServiceError';
    this.isValidationError = options.isValidationError ?? false;
    this.isNetworkError = options.isNetworkError ?? false;
    this.isServerError = options.isServerError ?? false;
    this.isRateLimited = options.isRateLimited ?? false;
    this.validationErrors = options.validationErrors ?? null;
    this.statusCode = options.statusCode ?? null;
  }
}

export const authService = {
  login: async (credentials: LoginCredentials): Promise<LoginResponse> => {
    try {
      const response = await api.post<LoginResponse>(API_ENDPOINTS.AUTH.LOGIN, credentials);
      return response.data;
    } catch (error) {
      console.error('Erro ao fazer login:', getErrorMessage(error));

      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiValidationError>;

        if (!axiosError.response) {
          throw new AuthServiceError(
            'Sem conexão com a internet. Verifique sua conexão e tente novamente.',
            { isNetworkError: true }
          );
        }

        const { status, data: responseData } = axiosError.response;

        if (status === 429 || responseData?.errors?.email?.[0]?.includes('Too many')) {
          throw new AuthServiceError(
            'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            { isRateLimited: true, statusCode: status }
          );
        }

        if (status === 422 && responseData?.errors) {
          const translatedErrors = translateValidationErrors(responseData.errors);

          throw new AuthServiceError(responseData.message || 'Credenciais inválidas.', {
            isValidationError: true,
            validationErrors: translatedErrors,
            statusCode: status,
          });
        }

        if (status >= 500) {
          throw new AuthServiceError('Erro no servidor. Tente novamente em alguns instantes.', {
            isServerError: true,
            statusCode: status,
          });
        }

        throw new AuthServiceError(responseData?.message || 'Ocorreu um erro. Tente novamente.', {
          statusCode: status,
        });
      }

      throw new AuthServiceError('Erro inesperado. Tente novamente.');
    }
  },

  refreshToken: async (refreshToken: string): Promise<RefreshTokenResponse> => {
    try {
      const response = await api.post<RefreshTokenResponse>(
        API_ENDPOINTS.AUTH.REFRESH,
        {},
        {
          headers: {
            Authorization: `Bearer ${refreshToken}`,
          },
        }
      );
      return response.data;
    } catch (error) {
      console.error('Erro ao renovar token:', getErrorMessage(error));

      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiValidationError>;

        if (!axiosError.response) {
          throw new AuthServiceError(
            'Sem conexão com a internet. Verifique sua conexão e tente novamente.',
            { isNetworkError: true }
          );
        }

        const { status, data: responseData } = axiosError.response;

        if (status === 401) {
          throw new AuthServiceError('Sessão expirada. Faça login novamente.', {
            statusCode: status,
          });
        }

        if (status === 403) {
          throw new AuthServiceError(
            responseData?.message || 'Acesso negado. Sua conta pode estar pendente de aprovação.',
            { statusCode: status }
          );
        }

        if (status >= 500) {
          throw new AuthServiceError('Erro no servidor. Tente novamente em alguns instantes.', {
            isServerError: true,
            statusCode: status,
          });
        }

        throw new AuthServiceError(responseData?.message || 'Erro ao renovar sessão.', {
          statusCode: status,
        });
      }

      throw new AuthServiceError('Erro inesperado ao renovar sessão.');
    }
  },

  logout: async (): Promise<void> => {
    try {
      await api.post(API_ENDPOINTS.AUTH.LOGOUT);
    } catch (error) {
      console.error('Erro ao fazer logout:', getErrorMessage(error));
    }
  },
};

function translateValidationErrors(errors: Record<string, string[]>): Record<string, string[]> {
  const translations: Record<string, string> = {
    'These credentials do not match our records.': 'E-mail ou senha incorretos.',
    'The email field is required.': 'O e-mail é obrigatório.',
    'The password field is required.': 'A senha é obrigatória.',
    'The email must be a valid email address.': 'O e-mail deve ser válido.',
  };

  const translated: Record<string, string[]> = {};

  for (const [field, messages] of Object.entries(errors)) {
    translated[field] = messages.map((msg) => translations[msg] || msg);
  }

  return translated;
}

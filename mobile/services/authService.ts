import api from './api';
import axios, { AxiosError } from 'axios';
import { User } from '@/stores/authStore';

export interface LoginCredentials {
  email: string;
  password: string;
  device_name: string;
}

export interface LoginResponse {
  data: {
    token: string;
    user: User;
  };
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
      const response = await api.post<LoginResponse>('/login', credentials);
      return response.data;
    } catch (error) {
      console.error('Erro ao fazer login:', error);

      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiValidationError>;

        // Erro de rede (sem conexão)
        if (!axiosError.response) {
          throw new AuthServiceError(
            'Sem conexão com a internet. Verifique sua conexão e tente novamente.',
            { isNetworkError: true }
          );
        }

        const { status, data: responseData } = axiosError.response;

        // Rate limiting (429 ou mensagem específica no 422)
        if (status === 429 || responseData?.errors?.email?.[0]?.includes('Too many')) {
          throw new AuthServiceError(
            'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
            { isRateLimited: true, statusCode: status }
          );
        }

        // Erro de validação (422)
        if (status === 422 && responseData?.errors) {
          // Traduzir mensagens comuns
          const translatedErrors = translateValidationErrors(responseData.errors);

          throw new AuthServiceError(responseData.message || 'Credenciais inválidas.', {
            isValidationError: true,
            validationErrors: translatedErrors,
            statusCode: status,
          });
        }

        // Erro de servidor (500+)
        if (status >= 500) {
          throw new AuthServiceError('Erro no servidor. Tente novamente em alguns instantes.', {
            isServerError: true,
            statusCode: status,
          });
        }

        // Outros erros HTTP
        throw new AuthServiceError(responseData?.message || 'Ocorreu um erro. Tente novamente.', {
          statusCode: status,
        });
      }

      // Erro desconhecido
      throw new AuthServiceError('Erro inesperado. Tente novamente.');
    }
  },

  logout: async (): Promise<void> => {
    try {
      await api.post('/logout');
    } catch (error) {
      // Mesmo se der erro, limpa local
      console.error('Erro ao fazer logout:', error);
    }
  },
};

/**
 * Traduz mensagens de erro da API para português
 */
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

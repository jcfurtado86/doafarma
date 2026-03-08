import { apiClient } from './api';
import axios, { AxiosError } from 'axios';
import { logger } from '@/utils/logger';

export interface ReceptorRegistrationData {
  name: string;
  email: string;
  cpf: string;
  phone_number: string;
  password: string;
  password_confirmation: string;
  device_name: string;
  terms_accepted: boolean;
}

export interface ReceptorRegistrationResponse {
  data: {
    user: {
      id: number;
      name: string;
      email: string;
      phone_number: string;
      role: 'doctor' | 'receptor';
      status: 'pending' | 'approved' | 'rejected';
    };
    access_token: string;
    refresh_token: string;
    expires_in: number;
    refresh_expires_in: number;
  };
  message: string;
}

export interface ApiValidationError {
  message: string;
  errors: Record<string, string[]>;
}

export class ReceptorServiceError extends Error {
  isValidationError: boolean;
  isNetworkError: boolean;
  isServerError: boolean;
  validationErrors: Record<string, string[]> | null;
  statusCode: number | null;

  constructor(
    message: string,
    options: {
      isValidationError?: boolean;
      isNetworkError?: boolean;
      isServerError?: boolean;
      validationErrors?: Record<string, string[]> | null;
      statusCode?: number | null;
    } = {}
  ) {
    super(message);
    this.name = 'ReceptorServiceError';
    this.isValidationError = options.isValidationError ?? false;
    this.isNetworkError = options.isNetworkError ?? false;
    this.isServerError = options.isServerError ?? false;
    this.validationErrors = options.validationErrors ?? null;
    this.statusCode = options.statusCode ?? null;
  }
}

export const receptorService = {
  register: async (data: ReceptorRegistrationData): Promise<ReceptorRegistrationResponse> => {
    try {
      const response = await apiClient.post<ReceptorRegistrationResponse>(
        '/register/receptor',
        data
      );
      return response.data;
    } catch (error) {
      logger.error('Erro ao registrar receptor:', error);

      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiValidationError>;

        if (!axiosError.response) {
          throw new ReceptorServiceError(
            'Sem conexão com a internet. Verifique sua conexão e tente novamente.',
            { isNetworkError: true }
          );
        }

        const { status, data: responseData } = axiosError.response;

        if (status === 422 && responseData?.errors) {
          throw new ReceptorServiceError(responseData.message || 'Dados inválidos.', {
            isValidationError: true,
            validationErrors: responseData.errors,
            statusCode: status,
          });
        }

        if (status >= 500) {
          throw new ReceptorServiceError('Erro no servidor. Tente novamente em alguns instantes.', {
            isServerError: true,
            statusCode: status,
          });
        }

        throw new ReceptorServiceError(
          responseData?.message || 'Ocorreu um erro. Tente novamente.',
          { statusCode: status }
        );
      }

      throw new ReceptorServiceError('Erro inesperado. Tente novamente.');
    }
  },
};

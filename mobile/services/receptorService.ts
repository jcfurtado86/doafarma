import api from './api';
import axios, { AxiosError } from 'axios';

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
      role: string;
    };
    token: string;
  };
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
      const response = await api.post<ReceptorRegistrationResponse>('/register/receptor', data);
      return response.data;
    } catch (error) {
      console.error('Erro ao registrar receptor:', error);

      if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiValidationError>;

        // Erro de rede (sem conexão)
        if (!axiosError.response) {
          throw new ReceptorServiceError(
            'Sem conexão com a internet. Verifique sua conexão e tente novamente.',
            { isNetworkError: true }
          );
        }

        const { status, data: responseData } = axiosError.response;

        // Erro de validação (422)
        if (status === 422 && responseData?.errors) {
          throw new ReceptorServiceError(responseData.message || 'Dados inválidos.', {
            isValidationError: true,
            validationErrors: responseData.errors,
            statusCode: status,
          });
        }

        // Erro de servidor (500+)
        if (status >= 500) {
          throw new ReceptorServiceError(
            'Erro no servidor. Tente novamente em alguns instantes.',
            { isServerError: true, statusCode: status }
          );
        }

        // Outros erros HTTP
        throw new ReceptorServiceError(
          responseData?.message || 'Ocorreu um erro. Tente novamente.',
          { statusCode: status }
        );
      }

      // Erro desconhecido
      throw new ReceptorServiceError('Erro inesperado. Tente novamente.');
    }
  },
};

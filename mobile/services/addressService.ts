import { apiClient } from './api';
import { Address, CreateAddressData, UpdateAddressData } from '@/types/address';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export class AddressServiceError extends Error {
  validationErrors: Record<string, string[]> | null;

  constructor(message: string, validationErrors: Record<string, string[]> | null = null) {
    super(message);
    this.name = 'AddressServiceError';
    this.validationErrors = validationErrors;
  }
}

// O interceptor de resposta do api.ts já normaliza todo 422 anexando
// validationErrors ao erro rejeitado; aqui só preservamos esse dado.
const toAddressServiceError = (error: unknown): AddressServiceError => {
  const validationErrors =
    typeof error === 'object' && error !== null && 'validationErrors' in error
      ? ((error as { validationErrors?: Record<string, string[]> }).validationErrors ?? null)
      : null;
  const hasFieldErrors = validationErrors !== null && Object.keys(validationErrors).length > 0;

  return new AddressServiceError(
    extractErrorMessage(error),
    hasFieldErrors ? validationErrors : null
  );
};

export const addressService = {
  list: async (): Promise<Address[]> => {
    try {
      const response = await apiClient.get('/v1/addresses');
      return response.data.data;
    } catch (error: unknown) {
      throw toAddressServiceError(error);
    }
  },

  create: async (data: CreateAddressData): Promise<Address> => {
    try {
      const response = await apiClient.post('/v1/addresses', data);
      return response.data.data;
    } catch (error: unknown) {
      throw toAddressServiceError(error);
    }
  },

  update: async (id: number, data: UpdateAddressData): Promise<Address> => {
    try {
      const response = await apiClient.put(`/v1/addresses/${id}`, data);
      return response.data.data;
    } catch (error: unknown) {
      throw toAddressServiceError(error);
    }
  },

  delete: async (id: number): Promise<void> => {
    try {
      await apiClient.delete(`/v1/addresses/${id}`);
    } catch (error: unknown) {
      throw toAddressServiceError(error);
    }
  },

  setDefault: async (id: number): Promise<Address> => {
    try {
      const response = await apiClient.patch(`/v1/addresses/${id}/default`);
      return response.data.data;
    } catch (error: unknown) {
      throw toAddressServiceError(error);
    }
  },
};

import { apiClient } from './api';
import {
  MedicationRequest,
  CreateMedicationRequestData,
  MedicationRequestStatus,
} from '@/types/medicationRequest';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationRequestService = {
  create: async (data: CreateMedicationRequestData): Promise<MedicationRequest> => {
    try {
      const response = await apiClient.post('/v1/medication-requests', data);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  list: async (): Promise<MedicationRequest[]> => {
    try {
      const response = await apiClient.get('/v1/medication-requests');
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listReceived: async (status?: MedicationRequestStatus): Promise<MedicationRequest[]> => {
    try {
      const params = status ? { status } : {};
      const response = await apiClient.get('/v1/medication-requests/received', { params });
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  confirm: async (id: number): Promise<MedicationRequest> => {
    try {
      const response = await apiClient.patch(`/v1/medication-requests/${id}/confirm`);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  reject: async (id: number): Promise<MedicationRequest> => {
    try {
      const response = await apiClient.patch(`/v1/medication-requests/${id}/reject`);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },
};

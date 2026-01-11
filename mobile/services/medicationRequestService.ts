import api from './api';
import {
  MedicationRequest,
  CreateMedicationRequestData,
  MedicationRequestStatus,
} from '@/types/medicationRequest';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationRequestService = {
  /**
   * Create a medication request (receptor only)
   */
  create: async (data: CreateMedicationRequestData): Promise<MedicationRequest> => {
    try {
      const response = await api.post('/v1/medication-requests', data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * List receptor's own medication requests
   */
  list: async (): Promise<MedicationRequest[]> => {
    try {
      const response = await api.get('/v1/medication-requests');
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * List doctor's received medication requests
   */
  listReceived: async (status?: MedicationRequestStatus): Promise<MedicationRequest[]> => {
    try {
      const params = status ? { status } : {};
      const response = await api.get('/v1/medication-requests/received', { params });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Doctor confirms a medication request
   */
  confirm: async (id: number): Promise<MedicationRequest> => {
    try {
      const response = await api.patch(`/v1/medication-requests/${id}/confirm`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Doctor rejects a medication request
   */
  reject: async (id: number): Promise<MedicationRequest> => {
    try {
      const response = await api.patch(`/v1/medication-requests/${id}/reject`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },
};

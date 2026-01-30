import { apiClient } from './api';
import {
  MedicationOffering,
  CreateMedicationOfferingData,
  UpdateMedicationOfferingData,
  MedicationOfferingSearchResult,
} from '@/types/medicationOffering';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationOfferingService = {
  list: async (includeDrug = true): Promise<MedicationOffering[]> => {
    try {
      const response = await apiClient.get('/v1/medication-offerings', {
        params: {
          include: includeDrug ? 'drug' : undefined,
        },
      });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  show: async (id: number, includeDrug = true): Promise<MedicationOffering> => {
    try {
      const response = await apiClient.get(`/v1/medication-offerings/${id}`, {
        params: {
          include: includeDrug ? 'drug' : undefined,
        },
      });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  create: async (data: CreateMedicationOfferingData): Promise<MedicationOffering> => {
    try {
      const response = await apiClient.post('/v1/medication-offerings', data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  update: async (id: number, data: UpdateMedicationOfferingData): Promise<MedicationOffering> => {
    try {
      const response = await apiClient.put(`/v1/medication-offerings/${id}`, data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  delete: async (id: number): Promise<void> => {
    try {
      await apiClient.delete(`/v1/medication-offerings/${id}`);
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  search: async (query: string): Promise<MedicationOfferingSearchResult[]> => {
    try {
      const response = await apiClient.get('/v1/medication-offerings/search', {
        params: { q: query },
      });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },
};

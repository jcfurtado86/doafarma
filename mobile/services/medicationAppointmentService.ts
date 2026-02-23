import { apiClient } from './api';
import {
  MedicationAppointment,
  CreateMedicationAppointmentData,
  CounterProposeAppointmentData,
  MedicationAppointmentStatus,
} from '@/types/medicationAppointment';
import { PaginatedResponse } from '@/types/pagination';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationAppointmentService = {
  create: async (data: CreateMedicationAppointmentData): Promise<MedicationAppointment> => {
    try {
      const response = await apiClient.post('/v1/medication-appointments', data);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  list: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await apiClient.get('/v1/medication-appointments', { params });
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listReceived: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await apiClient.get('/v1/medication-appointments/received', { params });
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  accept: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await apiClient.patch(`/v1/medication-appointments/${id}/accept`);
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  counterPropose: async (
    id: number,
    data: CounterProposeAppointmentData
  ): Promise<MedicationAppointment> => {
    try {
      const response = await apiClient.patch(
        `/v1/medication-appointments/${id}/counter-propose`,
        data
      );
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  confirmDeliveryReceptor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await apiClient.patch(
        `/v1/medication-appointments/${id}/confirm-delivery-receptor`
      );
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  confirmDeliveryDoctor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await apiClient.patch(
        `/v1/medication-appointments/${id}/confirm-delivery-doctor`
      );
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listHistory: async (): Promise<MedicationAppointment[]> => {
    try {
      const response = await apiClient.get('/v1/medication-appointments/history');
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listHistoryPaginated: async (page = 1): Promise<PaginatedResponse<MedicationAppointment>> => {
    try {
      const response = await apiClient.get('/v1/medication-appointments/history', {
        params: { page },
      });
      return {
        data: response.data.data,
        meta: response.data.meta,
        links: response.data.links,
      };
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listDoctorHistory: async (): Promise<MedicationAppointment[]> => {
    try {
      const response = await apiClient.get('/v1/medication-appointments/doctor-history');
      return response.data.data;
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },

  listDoctorHistoryPaginated: async (
    page = 1
  ): Promise<PaginatedResponse<MedicationAppointment>> => {
    try {
      const response = await apiClient.get('/v1/medication-appointments/doctor-history', {
        params: { page },
      });
      return {
        data: response.data.data,
        meta: response.data.meta,
        links: response.data.links,
      };
    } catch (error: unknown) {
      throw new Error(extractErrorMessage(error));
    }
  },
};

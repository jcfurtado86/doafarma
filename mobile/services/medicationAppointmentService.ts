import api from './api';
import {
  MedicationAppointment,
  CreateMedicationAppointmentData,
  MedicationAppointmentStatus,
} from '@/types/medicationAppointment';
import { extractErrorMessage } from '@/utils/validation/errorHelpers';

export const medicationAppointmentService = {
  /**
   * Create a medication appointment (receptor only)
   */
  create: async (data: CreateMedicationAppointmentData): Promise<MedicationAppointment> => {
    try {
      const response = await api.post('/v1/medication-appointments', data);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * List receptor's own medication appointments
   */
  list: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await api.get('/v1/medication-appointments', { params });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * List doctor's received medication appointments
   */
  listReceived: async (status?: MedicationAppointmentStatus): Promise<MedicationAppointment[]> => {
    try {
      const params = status ? { status } : {};
      const response = await api.get('/v1/medication-appointments/received', { params });
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Receptor confirms delivery
   */
  confirmDeliveryReceptor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await api.patch(
        `/v1/medication-appointments/${id}/confirm-delivery-receptor`
      );
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },

  /**
   * Doctor confirms delivery
   */
  confirmDeliveryDoctor: async (id: number): Promise<MedicationAppointment> => {
    try {
      const response = await api.patch(`/v1/medication-appointments/${id}/confirm-delivery-doctor`);
      return response.data.data;
    } catch (error: any) {
      throw new Error(extractErrorMessage(error));
    }
  },
};

import { apiClient } from './api';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';
import { logger } from '@/utils/logger';

export const doctorService = {
  register: async (doctorData: DoctorRegistrationFormData) => {
    try {
      const response = await apiClient.post('/register/doctor', doctorData);
      return response.data;
    } catch (error) {
      logger.error('Erro ao registrar médico:', error);
      throw error;
    }
  },
};

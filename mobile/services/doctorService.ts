import api from './api';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';

export const doctorService = {
  register: async (doctorData: DoctorRegistrationFormData) => {
    try {
      const response = await api.post('/register/doctor', doctorData);
      return response.data;
    } catch (error) {
      console.error('Erro ao registrar médico:', error);
      throw error;
    }
  },
};

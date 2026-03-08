import { apiClient } from './api';
import { DoctorRegistrationFormData } from '@/stores/doctorRegistrationFormStore';
import { logger } from '@/utils/logger';

export interface DoctorRegistrationResponse {
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

export const doctorService = {
  register: async (doctorData: DoctorRegistrationFormData): Promise<DoctorRegistrationResponse> => {
    try {
      const response = await apiClient.post<DoctorRegistrationResponse>(
        '/register/doctor',
        doctorData
      );
      return response.data;
    } catch (error) {
      logger.error('Erro ao registrar médico:', error);
      throw error;
    }
  },
};

import api from './api';

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

export const receptorService = {
  register: async (data: ReceptorRegistrationData): Promise<ReceptorRegistrationResponse> => {
    try {
      const response = await api.post<ReceptorRegistrationResponse>('/register/receptor', data);
      return response.data;
    } catch (error) {
      console.error('Erro ao registrar receptor:', error);
      throw error;
    }
  },
};

import { doctorService } from '@/services/doctorService';
import { create } from 'zustand';
import * as Device from 'expo-device';
import { useAuthStore } from './authStore';
import { logger } from '@/utils/logger';

export interface DoctorRegistrationFormData {
  // first step
  name?: string;
  crm?: string;
  crm_uf?: string;
  ddd?: string;
  phone_number?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  device_name?: string;

  // final step
  addresses?: {
    location_name?: string;
    cep?: string;
    uf?: string;
    city?: string;
    neighborhood?: string;
    full_address?: string;
    number?: string;
    complement?: string;
  }[];
  terms_accepted?: boolean;
}

interface DoctorRegistrationFormStore {
  doctorRegistrationFormData: DoctorRegistrationFormData;
  isLoading: boolean;
  error: string | null;
  validationErrors: Record<string, string[]> | null;
  updateDoctorRegistrationFormData: (data: Partial<DoctorRegistrationFormData>) => void;
  submitDoctorRegistrationForm: () => Promise<boolean>;
  clearValidationErrors: () => void;
}

export const useDoctorRegistrationFormStore = create<DoctorRegistrationFormStore>((set, get) => ({
  doctorRegistrationFormData: {
    name: '',
    crm: '',
    crm_uf: '',
    ddd: '',
    phone_number: '',
    email: '',
    password: '',
    password_confirmation: '',
    device_name: '',
    addresses: [],
    terms_accepted: true,
  },
  isLoading: false,
  error: null,
  validationErrors: null,
  clearValidationErrors: () => set({ validationErrors: null }),
  updateDoctorRegistrationFormData: (data) =>
    set((state) => ({
      doctorRegistrationFormData: {
        ...state.doctorRegistrationFormData,
        ...data,
      },
    })),
  submitDoctorRegistrationForm: async () => {
    set({ isLoading: true, error: null, validationErrors: null });
    try {
      const formData = { ...get().doctorRegistrationFormData };

      if (formData.ddd && formData.phone_number) {
        formData.phone_number = `${formData.ddd}${formData.phone_number}`;
        delete formData.ddd;
      }

      if (
        formData.addresses &&
        formData.addresses[0].full_address &&
        formData.addresses[0].number &&
        formData.addresses[0].neighborhood &&
        formData.addresses[0].city &&
        formData.addresses[0].uf
      ) {
        formData.addresses[0].full_address = `${formData.addresses[0].full_address}, ${formData.addresses[0].number} - ${formData.addresses[0].neighborhood}, ${formData.addresses[0].city} - ${formData.addresses[0].uf}`;
        delete formData.addresses[0].number;
        delete formData.addresses[0].neighborhood;
        delete formData.addresses[0].city;
        delete formData.addresses[0].uf;
      }

      formData.device_name = await getDeviceName();

      const responseData = await doctorService.register(formData);

      const { user, token } = responseData;

      await useAuthStore.getState().saveSession(user, token);

      set({ isLoading: false });
      return true;
    } catch (error: any) {
      if (error.isValidationError) {
        set({
          isLoading: false,
          validationErrors: error.validationErrors,
          error: error.message,
        });
      } else {
        set({
          isLoading: false,
          error: error instanceof Error ? error.message : 'Erro ao cadastrar médico',
        });
      }

      return false;
    }
  },
}));

async function getDeviceName(): Promise<string> {
  try {
    const deviceName =
      Device.deviceName || `${Device.brand || ''} ${Device.modelName || ''}`.trim();

    return deviceName || 'Dispositivo Desconhecido';
  } catch (error) {
    logger.warn('Erro ao obter nome do dispositivo:', error);
    return 'Mobile App';
  }
}

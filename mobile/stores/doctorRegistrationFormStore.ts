import { doctorService } from '@/services/doctorService';
import { getErrorMessage } from '@/types/errors';
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
    label?: string;
    cep?: string;
    uf?: string;
    city?: string;
    neighborhood?: string;
    street?: string;
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

interface ValidationError {
  isValidationError: true;
  validationErrors: Record<string, string[]>;
  message: string;
}

function isValidationError(error: unknown): error is ValidationError {
  return (
    typeof error === 'object' &&
    error !== null &&
    'isValidationError' in error &&
    (error as Record<string, unknown>).isValidationError === true
  );
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

      formData.device_name = await getDeviceName();

      const responseData = await doctorService.register(formData);

      const { user, access_token, refresh_token, expires_in } = responseData.data;

      await useAuthStore.getState().saveSession(user, access_token, refresh_token, expires_in);

      set({ isLoading: false });
      return true;
    } catch (error: unknown) {
      if (isValidationError(error)) {
        set({
          isLoading: false,
          validationErrors: error.validationErrors,
          error: error.message,
        });
      } else {
        set({
          isLoading: false,
          error: getErrorMessage(error, 'Erro ao cadastrar médico'),
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

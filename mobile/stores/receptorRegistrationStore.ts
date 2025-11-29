import {
  receptorService,
  ReceptorRegistrationData,
  ReceptorServiceError,
} from '@/services/receptorService';
import { create } from 'zustand';
import * as Device from 'expo-device';
import { useAuthStore } from './authStore';
import { cleanNumeric } from '@/utils/validation/receptorValidation';

export interface ReceptorRegistrationFormData {
  name: string;
  email: string;
  cpf: string;
  phone_number: string;
  password: string;
  password_confirmation: string;
  terms_accepted: boolean;
}

interface ReceptorRegistrationFormStore {
  formData: ReceptorRegistrationFormData;
  isLoading: boolean;
  error: string | null;
  validationErrors: Record<string, string[]> | null;
  updateFormData: (data: Partial<ReceptorRegistrationFormData>) => void;
  submitRegistration: () => Promise<boolean>;
  clearErrors: () => void;
  resetForm: () => void;
}

const initialFormData: ReceptorRegistrationFormData = {
  name: '',
  email: '',
  cpf: '',
  phone_number: '',
  password: '',
  password_confirmation: '',
  terms_accepted: false,
};

export const useReceptorRegistrationStore = create<ReceptorRegistrationFormStore>((set, get) => ({
  formData: { ...initialFormData },
  isLoading: false,
  error: null,
  validationErrors: null,

  updateFormData: (data) =>
    set((state) => ({
      formData: {
        ...state.formData,
        ...data,
      },
    })),

  clearErrors: () => set({ error: null, validationErrors: null }),

  resetForm: () => set({ formData: { ...initialFormData }, error: null, validationErrors: null }),

  submitRegistration: async () => {
    set({ isLoading: true, error: null, validationErrors: null });

    try {
      const { formData } = get();
      const deviceName = await getDeviceName();

      const registrationData: ReceptorRegistrationData = {
        name: formData.name.trim(),
        email: formData.email.trim().toLowerCase(),
        cpf: cleanNumeric(formData.cpf),
        phone_number: cleanNumeric(formData.phone_number),
        password: formData.password,
        password_confirmation: formData.password_confirmation,
        device_name: deviceName,
        terms_accepted: formData.terms_accepted,
      };

      const response = await receptorService.register(registrationData);

      const { user, token } = response.data;

      await useAuthStore.getState().saveSession(user, token);

      set({ isLoading: false });
      return true;
    } catch (error: unknown) {
      if (error instanceof ReceptorServiceError) {
        if (error.isValidationError) {
          set({
            isLoading: false,
            validationErrors: error.validationErrors,
            error: error.message,
          });
        } else if (error.isNetworkError) {
          set({
            isLoading: false,
            error: error.message,
          });
        } else if (error.isServerError) {
          set({
            isLoading: false,
            error: error.message,
          });
        } else {
          set({
            isLoading: false,
            error: error.message,
          });
        }
      } else {
        set({
          isLoading: false,
          error: error instanceof Error ? error.message : 'Erro ao cadastrar. Tente novamente.',
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
    console.warn('Erro ao obter nome do dispositivo:', error);
    return 'Mobile App';
  }
}

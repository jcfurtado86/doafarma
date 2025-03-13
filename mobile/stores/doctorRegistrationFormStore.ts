import { doctorService } from '@/services/doctorService';
import { create } from 'zustand';
import * as Device from 'expo-device';

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
    name?: string;
    cep?: string;
    uf?: string;
    city?: string;
    neighborhood?: string;
    full_address?: string;
    number?: string;
    complement?: string;
  }[];
}

interface DoctorRegistrationFormStore {
  doctorRegistrationFormData: DoctorRegistrationFormData;
  isLoading: boolean;
  error: string | null;
  updateDoctorRegistrationFormData: (data: Partial<DoctorRegistrationFormData>) => void;
  submitDoctorRegistrationForm: () => Promise<boolean>;
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
  },
  isLoading: false,
  error: null,
  updateDoctorRegistrationFormData: (data) =>
    set((state) => ({
      doctorRegistrationFormData: {
        ...state.doctorRegistrationFormData,
        ...data,
      },
    })),
  submitDoctorRegistrationForm: async () => {
    set({ isLoading: true, error: null });
    try {
      const formData = { ...get().doctorRegistrationFormData };

      formData.device_name = await getDeviceName();

      await doctorService.register(formData);
      set({ isLoading: false });
      return true;
    } catch (error) {
      set({
        isLoading: false,
        error: error instanceof Error ? error.message : 'Erro ao cadastrar médico',
      });
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

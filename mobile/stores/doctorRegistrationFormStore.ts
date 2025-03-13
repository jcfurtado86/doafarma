import { doctorService } from '@/services/doctorService';
import { create } from 'zustand';

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
      await doctorService.register(get().doctorRegistrationFormData);
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

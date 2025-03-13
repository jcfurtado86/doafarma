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
  updateDoctorRegistrationFormData: (data: Partial<DoctorRegistrationFormData>) => void;
}

export const useDoctorRegistrationFormStore = create<DoctorRegistrationFormStore>((set) => ({
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
  updateDoctorRegistrationFormData: (data) =>
    set((state) => ({
      doctorRegistrationFormData: {
        ...state.doctorRegistrationFormData,
        ...data,
      },
    })),
}));

import { create } from 'zustand';
import { medicationAppointmentService } from '@/services/medicationAppointmentService';
import {
  MedicationAppointment,
  MedicationAppointmentStatus,
  CreateMedicationAppointmentData,
} from '@/types/medicationAppointment';

interface MedicationAppointmentStoreState {
  // Receptor's own appointments
  appointments: MedicationAppointment[];
  // Doctor's received appointments
  receivedAppointments: MedicationAppointment[];
  isLoading: boolean;
  error: string | null;

  // Receptor actions
  fetchMyAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  createAppointment: (data: CreateMedicationAppointmentData) => Promise<MedicationAppointment>;
  confirmDeliveryReceptor: (id: number) => Promise<void>;

  // Doctor actions
  fetchReceivedAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  confirmDeliveryDoctor: (id: number) => Promise<void>;

  clearError: () => void;
}

export const useMedicationAppointmentStore = create<MedicationAppointmentStoreState>((set) => ({
  appointments: [],
  receivedAppointments: [],
  isLoading: false,
  error: null,

  fetchMyAppointments: async (status?: MedicationAppointmentStatus) => {
    set({ isLoading: true, error: null });
    try {
      const appointments = await medicationAppointmentService.list(status);
      set({ appointments, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  createAppointment: async (data: CreateMedicationAppointmentData) => {
    set({ isLoading: true, error: null });
    try {
      const newAppointment = await medicationAppointmentService.create(data);
      set((state) => ({
        appointments: [newAppointment, ...state.appointments],
        isLoading: false,
        error: null,
      }));
      return newAppointment;
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  confirmDeliveryReceptor: async (id: number) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAppointment = await medicationAppointmentService.confirmDeliveryReceptor(id);
      set((state) => ({
        appointments: state.appointments.map((appointment) =>
          appointment.id === id ? updatedAppointment : appointment
        ),
        isLoading: false,
        error: null,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  fetchReceivedAppointments: async (status?: MedicationAppointmentStatus) => {
    set({ isLoading: true, error: null });
    try {
      const receivedAppointments = await medicationAppointmentService.listReceived(status);
      set({ receivedAppointments, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  confirmDeliveryDoctor: async (id: number) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAppointment = await medicationAppointmentService.confirmDeliveryDoctor(id);
      set((state) => ({
        receivedAppointments: state.receivedAppointments.map((appointment) =>
          appointment.id === id ? updatedAppointment : appointment
        ),
        isLoading: false,
        error: null,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
}));

import { create } from 'zustand';
import { medicationAppointmentService } from '@/services/medicationAppointmentService';
import {
  MedicationAppointment,
  MedicationAppointmentStatus,
  CreateMedicationAppointmentData,
  CounterProposeAppointmentData,
} from '@/types/medicationAppointment';

interface MedicationAppointmentStoreState {
  // Receptor's own appointments
  appointments: MedicationAppointment[];
  // Doctor's received appointments
  receivedAppointments: MedicationAppointment[];
  // Receptor's medication history (completed appointments)
  history: MedicationAppointment[];
  // Doctor's donation history (completed appointments)
  donationHistory: MedicationAppointment[];
  isLoading: boolean;
  error: string | null;

  // Receptor actions
  fetchMyAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  createAppointment: (data: CreateMedicationAppointmentData) => Promise<MedicationAppointment>;
  confirmDeliveryReceptor: (id: number) => Promise<void>;
  fetchHistory: () => Promise<void>;

  // Doctor actions
  fetchReceivedAppointments: (status?: MedicationAppointmentStatus) => Promise<void>;
  confirmDeliveryDoctor: (id: number) => Promise<void>;
  fetchDoctorHistory: () => Promise<void>;

  // Negotiation actions (both roles)
  acceptAppointment: (id: number, isDoctor: boolean) => Promise<void>;
  counterProposeAppointment: (
    id: number,
    data: CounterProposeAppointmentData,
    isDoctor: boolean
  ) => Promise<void>;

  clearError: () => void;
}

export const useMedicationAppointmentStore = create<MedicationAppointmentStoreState>((set) => ({
  appointments: [],
  receivedAppointments: [],
  history: [],
  donationHistory: [],
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

  fetchHistory: async () => {
    set({ isLoading: true, error: null });
    try {
      const history = await medicationAppointmentService.listHistory();
      set({ history, isLoading: false, error: null });
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

  fetchDoctorHistory: async () => {
    set({ isLoading: true, error: null });
    try {
      const donationHistory = await medicationAppointmentService.listDoctorHistory();
      set({ donationHistory, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  acceptAppointment: async (id: number, isDoctor: boolean) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAppointment = await medicationAppointmentService.accept(id);
      set((state) => {
        if (isDoctor) {
          return {
            receivedAppointments: state.receivedAppointments.map((appointment) =>
              appointment.id === id ? updatedAppointment : appointment
            ),
            isLoading: false,
            error: null,
          };
        } else {
          return {
            appointments: state.appointments.map((appointment) =>
              appointment.id === id ? updatedAppointment : appointment
            ),
            isLoading: false,
            error: null,
          };
        }
      });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  counterProposeAppointment: async (
    id: number,
    data: CounterProposeAppointmentData,
    isDoctor: boolean
  ) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAppointment = await medicationAppointmentService.counterPropose(id, data);
      set((state) => {
        if (isDoctor) {
          return {
            receivedAppointments: state.receivedAppointments.map((appointment) =>
              appointment.id === id ? updatedAppointment : appointment
            ),
            isLoading: false,
            error: null,
          };
        } else {
          return {
            appointments: state.appointments.map((appointment) =>
              appointment.id === id ? updatedAppointment : appointment
            ),
            isLoading: false,
            error: null,
          };
        }
      });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
}));

import { create } from 'zustand';
import { doctorRatingService } from '@/services/doctorRatingService';
import { DoctorRating, CreateDoctorRatingData } from '@/types/doctorRating';

interface DoctorRatingStoreState {
  // Ratings for a specific doctor
  doctorRatings: DoctorRating[];
  // Currently selected doctor ID for ratings
  selectedDoctorId: number | null;
  isLoading: boolean;
  error: string | null;

  // Actions
  createRating: (appointmentId: number, data: CreateDoctorRatingData) => Promise<DoctorRating>;
  fetchDoctorRatings: (doctorId: number) => Promise<void>;
  clearError: () => void;
  clearRatings: () => void;
}

export const useDoctorRatingStore = create<DoctorRatingStoreState>((set) => ({
  doctorRatings: [],
  selectedDoctorId: null,
  isLoading: false,
  error: null,

  createRating: async (appointmentId: number, data: CreateDoctorRatingData) => {
    set({ isLoading: true, error: null });
    try {
      const newRating = await doctorRatingService.create(appointmentId, data);
      set({ isLoading: false, error: null });
      return newRating;
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  fetchDoctorRatings: async (doctorId: number) => {
    set({ isLoading: true, error: null, selectedDoctorId: doctorId });
    try {
      const doctorRatings = await doctorRatingService.listByDoctor(doctorId);
      set({ doctorRatings, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
  clearRatings: () => set({ doctorRatings: [], selectedDoctorId: null }),
}));

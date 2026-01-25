import { create } from 'zustand';
import { doctorRatingService } from '@/services/doctorRatingService';
import { DoctorRating, CreateDoctorRatingData, RatingSummary } from '@/types/doctorRating';

interface DoctorRatingStoreState {
  // Ratings for a specific doctor
  doctorRatings: DoctorRating[];
  // Currently selected doctor ID for ratings
  selectedDoctorId: number | null;
  // Current rating being viewed/edited
  currentRating: DoctorRating | null;
  // My ratings summary (for doctors)
  myRatingsSummary: RatingSummary | null;
  myRatings: DoctorRating[];
  isLoading: boolean;
  error: string | null;

  // Actions
  createRating: (appointmentId: number, data: CreateDoctorRatingData) => Promise<DoctorRating>;
  updateRating: (ratingId: number, data: CreateDoctorRatingData) => Promise<DoctorRating>;
  fetchRatingByAppointment: (appointmentId: number) => Promise<DoctorRating | null>;
  fetchDoctorRatings: (doctorId: number) => Promise<void>;
  fetchMyRatings: () => Promise<void>;
  clearError: () => void;
  clearRatings: () => void;
  clearCurrentRating: () => void;
  clearMyRatings: () => void;
}

export const useDoctorRatingStore = create<DoctorRatingStoreState>((set) => ({
  doctorRatings: [],
  selectedDoctorId: null,
  currentRating: null,
  myRatingsSummary: null,
  myRatings: [],
  isLoading: false,
  error: null,

  createRating: async (appointmentId: number, data: CreateDoctorRatingData) => {
    set({ isLoading: true, error: null });
    try {
      const newRating = await doctorRatingService.create(appointmentId, data);
      set({ isLoading: false, error: null, currentRating: newRating });
      return newRating;
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  updateRating: async (ratingId: number, data: CreateDoctorRatingData) => {
    set({ isLoading: true, error: null });
    try {
      const updatedRating = await doctorRatingService.update(ratingId, data);
      set({ isLoading: false, error: null, currentRating: updatedRating });
      return updatedRating;
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  fetchRatingByAppointment: async (appointmentId: number) => {
    set({ isLoading: true, error: null });
    try {
      const rating = await doctorRatingService.getByAppointment(appointmentId);
      set({ isLoading: false, error: null, currentRating: rating });
      return rating;
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

  fetchMyRatings: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await doctorRatingService.getMyRatings();
      set({
        myRatingsSummary: response.summary,
        myRatings: response.ratings,
        isLoading: false,
        error: null,
      });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
  clearRatings: () => set({ doctorRatings: [], selectedDoctorId: null }),
  clearCurrentRating: () => set({ currentRating: null }),
  clearMyRatings: () => set({ myRatings: [], myRatingsSummary: null }),
}));

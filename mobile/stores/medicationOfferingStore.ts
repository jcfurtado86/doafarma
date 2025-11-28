import { create } from 'zustand';
import { medicationOfferingService } from '@/services/medicationOfferingService';
import {
  MedicationOffering,
  CreateMedicationOfferingData,
  UpdateMedicationOfferingData,
} from '@/types/medicationOffering';

interface MedicationOfferingStoreState {
  offerings: MedicationOffering[];
  currentOffering: MedicationOffering | null;
  isLoading: boolean;
  error: string | null;

  fetchOfferings: () => Promise<void>;
  fetchOffering: (id: number) => Promise<void>;
  createOffering: (data: CreateMedicationOfferingData) => Promise<void>;
  updateOffering: (id: number, data: UpdateMedicationOfferingData) => Promise<void>;
  deleteOffering: (id: number) => Promise<void>;
  clearError: () => void;
}

export const useMedicationOfferingStore = create<MedicationOfferingStoreState>((set) => ({
  offerings: [],
  currentOffering: null,
  isLoading: false,
  error: null,

  fetchOfferings: async () => {
    set({ isLoading: true, error: null });
    try {
      const offerings = await medicationOfferingService.list();
      set({ offerings, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  fetchOffering: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const offering = await medicationOfferingService.show(id);
      set({ currentOffering: offering, isLoading: false, error: null });
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  createOffering: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const newOffering = await medicationOfferingService.create(data);
      set((state) => ({
        offerings: [newOffering, ...state.offerings],
        isLoading: false,
        error: null,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  updateOffering: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const updatedOffering = await medicationOfferingService.update(id, data);
      set((state) => ({
        offerings: state.offerings.map((offering) =>
          offering.id === id ? updatedOffering : offering
        ),
        currentOffering: state.currentOffering?.id === id ? updatedOffering : state.currentOffering,
        isLoading: false,
        error: null,
      }));
    } catch (error: any) {
      set({ error: error.message, isLoading: false });
      throw error;
    }
  },

  deleteOffering: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await medicationOfferingService.delete(id);
      set((state) => ({
        offerings: state.offerings.filter((offering) => offering.id !== id),
        currentOffering: state.currentOffering?.id === id ? null : state.currentOffering,
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

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

  // Actions
  fetchOfferings: () => Promise<void>;
  fetchOffering: (id: number) => Promise<void>;
  createOffering: (data: CreateMedicationOfferingData) => Promise<void>;
  updateOffering: (id: number, data: UpdateMedicationOfferingData) => Promise<void>;
  deleteOffering: (id: number) => Promise<void>;
  clearError: () => void;
}

export const useMedicationOfferingStore = create<MedicationOfferingStoreState>((set, get) => ({
  offerings: [],
  currentOffering: null,
  isLoading: false,
  error: null,

  fetchOfferings: async () => {
    set({ isLoading: true, error: null });
    try {
      const offerings = await medicationOfferingService.list();
      set({ offerings, isLoading: false });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Erro ao carregar ofertas de medicamentos',
        isLoading: false,
      });
    }
  },

  fetchOffering: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const offering = await medicationOfferingService.show(id);
      set({ currentOffering: offering, isLoading: false });
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Erro ao carregar oferta de medicamento',
        isLoading: false,
      });
    }
  },

  createOffering: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const newOffering = await medicationOfferingService.create(data);
      set((state) => ({
        offerings: [newOffering, ...state.offerings],
        isLoading: false,
      }));
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Erro ao criar oferta de medicamento',
        isLoading: false,
      });
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
      }));
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Erro ao atualizar oferta de medicamento',
        isLoading: false,
      });
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
      }));
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Erro ao deletar oferta de medicamento',
        isLoading: false,
      });
    }
  },

  clearError: () => set({ error: null }),
}));

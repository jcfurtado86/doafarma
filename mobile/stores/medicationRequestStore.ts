import { getErrorMessage } from '@/types/errors';
import { create } from 'zustand';
import { medicationRequestService } from '@/services/medicationRequestService';
import { MedicationRequest, MedicationRequestStatus } from '@/types/medicationRequest';

interface MedicationRequestStoreState {
  // Receptor's own requests
  requests: MedicationRequest[];
  // Doctor's received requests
  receivedRequests: MedicationRequest[];
  isLoading: boolean;
  error: string | null;

  // Receptor actions
  fetchMyRequests: () => Promise<void>;
  createRequest: (medicationOfferingId: number) => Promise<MedicationRequest>;

  // Doctor actions
  fetchReceivedRequests: (status?: MedicationRequestStatus) => Promise<void>;
  confirmRequest: (id: number) => Promise<void>;
  rejectRequest: (id: number) => Promise<void>;

  clearError: () => void;
}

export const useMedicationRequestStore = create<MedicationRequestStoreState>((set, get) => ({
  requests: [],
  receivedRequests: [],
  isLoading: false,
  error: null,

  fetchMyRequests: async () => {
    set({ isLoading: true, error: null });
    try {
      const requests = await medicationRequestService.list();
      set({ requests, isLoading: false, error: null });
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  createRequest: async (medicationOfferingId) => {
    set({ isLoading: true, error: null });
    try {
      const newRequest = await medicationRequestService.create({
        medication_offering_id: medicationOfferingId,
      });
      set((state) => ({
        requests: [newRequest, ...state.requests],
        isLoading: false,
        error: null,
      }));
      return newRequest;
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  fetchReceivedRequests: async (status?: MedicationRequestStatus) => {
    set({ isLoading: true, error: null });
    try {
      const receivedRequests = await medicationRequestService.listReceived(status);
      set({ receivedRequests, isLoading: false, error: null });
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  confirmRequest: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const updatedRequest = await medicationRequestService.confirm(id);
      set((state) => ({
        receivedRequests: state.receivedRequests.map((request) =>
          request.id === id ? updatedRequest : request
        ),
        isLoading: false,
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  rejectRequest: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const updatedRequest = await medicationRequestService.reject(id);
      set((state) => ({
        receivedRequests: state.receivedRequests.map((request) =>
          request.id === id ? updatedRequest : request
        ),
        isLoading: false,
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  clearError: () => set({ error: null }),
}));

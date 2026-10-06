import { getErrorMessage } from '@/types/errors';
import { create } from 'zustand';
import { addressService } from '@/services/addressService';
import { useAuthStore } from '@/stores/authStore';
import { Address, CreateAddressData, UpdateAddressData } from '@/types/address';

interface AddressStoreState {
  addresses: Address[];
  // Distingue "carregou e veio vazia" de "nunca carregou"; isLoading cobre só a busca da lista
  hasLoaded: boolean;
  isLoading: boolean;
  error: string | null;

  fetchAddresses: () => Promise<void>;
  ensureAddressesLoaded: () => Promise<void>;
  createAddress: (data: CreateAddressData) => Promise<void>;
  updateAddress: (id: number, data: UpdateAddressData) => Promise<void>;
  deleteAddress: (id: number) => Promise<void>;
  setDefaultAddress: (id: number) => Promise<void>;
  clearError: () => void;
  reset: () => void;
}

const initialState: Pick<AddressStoreState, 'addresses' | 'hasLoaded' | 'isLoading' | 'error'> = {
  addresses: [],
  hasLoaded: false,
  isLoading: false,
  error: null,
};

export const useAddressStore = create<AddressStoreState>((set, get) => ({
  ...initialState,

  fetchAddresses: async () => {
    set({ isLoading: true, error: null });
    try {
      const addresses = await addressService.list();
      set({ addresses, hasLoaded: true, isLoading: false, error: null });
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  ensureAddressesLoaded: async () => {
    if (get().hasLoaded) return;
    await get().fetchAddresses();
  },

  createAddress: async (data) => {
    set({ error: null });
    try {
      const newAddress = await addressService.create(data);
      set((state) => ({
        // Fim da lista: mesma ordem (por id) que o backend devolve
        addresses: [...state.addresses, newAddress],
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error) });
      throw error;
    }
  },

  updateAddress: async (id, data) => {
    set({ error: null });
    try {
      const updatedAddress = await addressService.update(id, data);
      set((state) => ({
        addresses: state.addresses.map((address) => (address.id === id ? updatedAddress : address)),
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error) });
      throw error;
    }
  },

  deleteAddress: async (id) => {
    set({ error: null });
    try {
      await addressService.delete(id);
      set((state) => ({
        addresses: state.addresses.filter((address) => address.id !== id),
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error) });
      throw error;
    }
  },

  setDefaultAddress: async (id) => {
    set({ error: null });
    try {
      const updatedAddress = await addressService.setDefault(id);
      set((state) => ({
        addresses: state.addresses.map((address) =>
          address.id === id ? updatedAddress : { ...address, is_default: false }
        ),
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error) });
      throw error;
    }
  },

  clearError: () => set({ error: null }),

  reset: () => set(initialState),
}));

// Os endereços são do médico logado: logout ou login de outra conta descarta a lista,
// senão a próxima pessoa no mesmo aparelho herda os endereços da anterior.
useAuthStore.subscribe((state, prevState) => {
  if (state.user?.id !== prevState.user?.id) {
    useAddressStore.getState().reset();
  }
});

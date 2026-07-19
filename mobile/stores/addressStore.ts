import { getErrorMessage } from '@/types/errors';
import { create } from 'zustand';
import { addressService } from '@/services/addressService';
import { Address, CreateAddressData, UpdateAddressData } from '@/types/address';

interface AddressStoreState {
  addresses: Address[];
  isLoading: boolean;
  error: string | null;

  fetchAddresses: () => Promise<void>;
  ensureAddressesLoaded: () => Promise<void>;
  createAddress: (data: CreateAddressData) => Promise<void>;
  updateAddress: (id: number, data: UpdateAddressData) => Promise<void>;
  deleteAddress: (id: number) => Promise<void>;
  setDefaultAddress: (id: number) => Promise<void>;
  clearError: () => void;
}

export const useAddressStore = create<AddressStoreState>((set, get) => ({
  addresses: [],
  isLoading: false,
  error: null,

  fetchAddresses: async () => {
    set({ isLoading: true, error: null });
    try {
      const addresses = await addressService.list();
      set({ addresses, isLoading: false, error: null });
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  ensureAddressesLoaded: async () => {
    if (get().addresses.length > 0) return;
    await get().fetchAddresses();
  },

  createAddress: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const newAddress = await addressService.create(data);
      set((state) => ({
        addresses: [newAddress, ...state.addresses],
        isLoading: false,
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  updateAddress: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAddress = await addressService.update(id, data);
      set((state) => ({
        addresses: state.addresses.map((address) => (address.id === id ? updatedAddress : address)),
        isLoading: false,
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  deleteAddress: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await addressService.delete(id);
      set((state) => ({
        addresses: state.addresses.filter((address) => address.id !== id),
        isLoading: false,
        error: null,
      }));
    } catch (error: unknown) {
      set({ error: getErrorMessage(error), isLoading: false });
      throw error;
    }
  },

  setDefaultAddress: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const updatedAddress = await addressService.setDefault(id);
      set((state) => ({
        addresses: state.addresses.map((address) =>
          address.id === id ? updatedAddress : { ...address, is_default: false }
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

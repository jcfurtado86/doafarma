import { useAddressStore } from '@/stores/addressStore';
import { addressService } from '@/services/addressService';
import { Address } from '@/types/address';

jest.mock('@/services/addressService', () => ({
  addressService: {
    list: jest.fn(),
    create: jest.fn(),
    update: jest.fn(),
    delete: jest.fn(),
    setDefault: jest.fn(),
  },
}));

const makeAddress = (overrides: Partial<Address> = {}): Address => ({
  id: 1,
  label: 'Consultório Centro',
  cep: '69900100',
  uf: 'AC',
  city: 'Rio Branco',
  neighborhood: 'Centro',
  street: 'Rua Principal',
  number: '100',
  complement: null,
  formatted_address: 'Rua Principal, 100, Centro, Rio Branco - AC, CEP 69900-100',
  is_default: false,
  ...overrides,
});

beforeEach(() => {
  jest.clearAllMocks();
  useAddressStore.setState({ addresses: [], isLoading: false, error: null });
});

describe('addressStore', () => {
  describe('fetchAddresses', () => {
    it('stores the fetched addresses', async () => {
      const addresses = [makeAddress(), makeAddress({ id: 2, label: 'Clínica Sul' })];
      (addressService.list as jest.Mock).mockResolvedValueOnce(addresses);

      await useAddressStore.getState().fetchAddresses();

      const state = useAddressStore.getState();
      expect(state.addresses).toEqual(addresses);
      expect(state.isLoading).toBe(false);
      expect(state.error).toBeNull();
    });

    it('sets error, stops loading and rethrows on failure', async () => {
      (addressService.list as jest.Mock).mockRejectedValueOnce(new Error('Falha de rede'));

      await expect(useAddressStore.getState().fetchAddresses()).rejects.toThrow('Falha de rede');

      const state = useAddressStore.getState();
      expect(state.error).toBe('Falha de rede');
      expect(state.isLoading).toBe(false);
    });
  });

  describe('ensureAddressesLoaded', () => {
    it('fetches when the store is empty', async () => {
      const addresses = [makeAddress()];
      (addressService.list as jest.Mock).mockResolvedValueOnce(addresses);

      await useAddressStore.getState().ensureAddressesLoaded();

      expect(addressService.list).toHaveBeenCalledTimes(1);
      expect(useAddressStore.getState().addresses).toEqual(addresses);
    });

    it('skips the fetch when addresses are already loaded', async () => {
      useAddressStore.setState({ addresses: [makeAddress()] });

      await useAddressStore.getState().ensureAddressesLoaded();

      expect(addressService.list).not.toHaveBeenCalled();
    });
  });

  describe('createAddress', () => {
    it('prepends the created address to the list', async () => {
      const existing = makeAddress();
      const created = makeAddress({ id: 2, label: 'Clínica Sul' });
      useAddressStore.setState({ addresses: [existing] });
      (addressService.create as jest.Mock).mockResolvedValueOnce(created);

      await useAddressStore.getState().createAddress({
        label: created.label,
        cep: created.cep,
        uf: created.uf,
        city: created.city,
        neighborhood: created.neighborhood,
        street: created.street,
        number: created.number,
      });

      expect(useAddressStore.getState().addresses).toEqual([created, existing]);
    });
  });

  describe('updateAddress', () => {
    it('replaces the matching address in the list', async () => {
      const original = makeAddress();
      const other = makeAddress({ id: 2, label: 'Clínica Sul' });
      const updated = makeAddress({ label: 'Consultório Novo' });
      useAddressStore.setState({ addresses: [original, other] });
      (addressService.update as jest.Mock).mockResolvedValueOnce(updated);

      await useAddressStore.getState().updateAddress(1, {
        label: updated.label,
        cep: updated.cep,
        uf: updated.uf,
        city: updated.city,
        neighborhood: updated.neighborhood,
        street: updated.street,
        number: updated.number,
      });

      expect(useAddressStore.getState().addresses).toEqual([updated, other]);
    });
  });

  describe('deleteAddress', () => {
    it('removes the address from the list', async () => {
      const first = makeAddress();
      const second = makeAddress({ id: 2, label: 'Clínica Sul' });
      useAddressStore.setState({ addresses: [first, second] });
      (addressService.delete as jest.Mock).mockResolvedValueOnce(undefined);

      await useAddressStore.getState().deleteAddress(1);

      expect(useAddressStore.getState().addresses).toEqual([second]);
    });

    it('keeps the list intact when deletion fails', async () => {
      const first = makeAddress();
      useAddressStore.setState({ addresses: [first] });
      (addressService.delete as jest.Mock).mockRejectedValueOnce(
        new Error('Este endereço está vinculado a um ou mais agendamentos.')
      );

      await expect(useAddressStore.getState().deleteAddress(1)).rejects.toThrow();

      const state = useAddressStore.getState();
      expect(state.addresses).toEqual([first]);
      expect(state.error).toBe('Este endereço está vinculado a um ou mais agendamentos.');
    });
  });

  describe('setDefaultAddress', () => {
    it('marks only the chosen address as default', async () => {
      const first = makeAddress({ is_default: true });
      const second = makeAddress({ id: 2, label: 'Clínica Sul' });
      useAddressStore.setState({ addresses: [first, second] });
      (addressService.setDefault as jest.Mock).mockResolvedValueOnce(
        makeAddress({ id: 2, is_default: true })
      );

      await useAddressStore.getState().setDefaultAddress(2);

      const state = useAddressStore.getState();
      expect(state.addresses.map((a) => ({ id: a.id, is_default: a.is_default }))).toEqual([
        { id: 1, is_default: false },
        { id: 2, is_default: true },
      ]);
    });
  });

  describe('clearError', () => {
    it('resets the error state', () => {
      useAddressStore.setState({ error: 'algo deu errado' });

      useAddressStore.getState().clearError();

      expect(useAddressStore.getState().error).toBeNull();
    });
  });
});

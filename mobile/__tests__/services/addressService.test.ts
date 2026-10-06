import { addressService, AddressServiceError } from '@/services/addressService';
import { Address, CreateAddressData } from '@/types/address';

jest.mock('@/services/api', () => {
  const mockGet = jest.fn();
  const mockPost = jest.fn();
  const mockPut = jest.fn();
  const mockDelete = jest.fn();
  const mockPatch = jest.fn();
  return {
    __esModule: true,
    default: {
      get: mockGet,
      post: mockPost,
      put: mockPut,
      delete: mockDelete,
      patch: mockPatch,
    },
    apiClient: {
      get: mockGet,
      post: mockPost,
      put: mockPut,
      delete: mockDelete,
      patch: mockPatch,
    },
  };
});

// eslint-disable-next-line import/first
import { apiClient } from '@/services/api';

const mockAddress: Address = {
  id: 1,
  label: 'Consultório Centro',
  cep: '69900100',
  uf: 'AC',
  city: 'Rio Branco',
  neighborhood: 'Centro',
  street: 'Rua Principal',
  number: '100',
  complement: 'Sala 2',
  formatted_address: 'Rua Principal, 100, Sala 2, Centro, Rio Branco - AC, CEP 69900-100',
  is_default: true,
};

const createData: CreateAddressData = {
  label: 'Consultório Centro',
  cep: '69900100',
  uf: 'AC',
  city: 'Rio Branco',
  neighborhood: 'Centro',
  street: 'Rua Principal',
  number: '100',
  complement: 'Sala 2',
};

// Espelha o formato rejeitado pelo interceptor de 422 do api.ts
// ({...error, isValidationError, validationErrors, message})
const validationError = {
  isValidationError: true,
  validationErrors: { cep: ['O campo CEP deve ter 8 caracteres.'] },
  message: 'O campo CEP é inválido.',
  response: {
    status: 422,
    data: {
      message: 'O campo CEP é inválido.',
      errors: { cep: ['O campo CEP deve ter 8 caracteres.'] },
    },
  },
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('addressService', () => {
  describe('list', () => {
    it('calls GET /v1/addresses and returns the address collection', async () => {
      (apiClient.get as jest.Mock).mockResolvedValueOnce({ data: { data: [mockAddress] } });

      const result = await addressService.list();

      expect(apiClient.get).toHaveBeenCalledWith('/v1/addresses');
      expect(result).toEqual([mockAddress]);
    });

    it('throws AddressServiceError on failure', async () => {
      (apiClient.get as jest.Mock).mockRejectedValueOnce(new Error('Network Error'));

      await expect(addressService.list()).rejects.toBeInstanceOf(AddressServiceError);
    });
  });

  describe('create', () => {
    it('calls POST /v1/addresses with the payload and returns the created address', async () => {
      (apiClient.post as jest.Mock).mockResolvedValueOnce({ data: { data: mockAddress } });

      const result = await addressService.create(createData);

      expect(apiClient.post).toHaveBeenCalledWith('/v1/addresses', createData);
      expect(result).toEqual(mockAddress);
    });

    it('preserves field validation errors from a 422 response', async () => {
      (apiClient.post as jest.Mock).mockRejectedValueOnce(validationError);

      try {
        await addressService.create(createData);
        throw new Error('expected addressService.create to throw');
      } catch (error) {
        expect(error).toBeInstanceOf(AddressServiceError);
        expect((error as AddressServiceError).validationErrors).toEqual({
          cep: ['O campo CEP deve ter 8 caracteres.'],
        });
      }
    });
  });

  describe('update', () => {
    it('calls PUT /v1/addresses/{id} with the payload and returns the updated address', async () => {
      (apiClient.put as jest.Mock).mockResolvedValueOnce({ data: { data: mockAddress } });

      const result = await addressService.update(1, createData);

      expect(apiClient.put).toHaveBeenCalledWith('/v1/addresses/1', createData);
      expect(result).toEqual(mockAddress);
    });
  });

  describe('delete', () => {
    it('calls DELETE /v1/addresses/{id}', async () => {
      (apiClient.delete as jest.Mock).mockResolvedValueOnce({});

      await addressService.delete(1);

      expect(apiClient.delete).toHaveBeenCalledWith('/v1/addresses/1');
    });

    it('surfaces the backend message when the address is linked to an appointment', async () => {
      (apiClient.delete as jest.Mock).mockRejectedValueOnce({
        isValidationError: true,
        validationErrors: {
          address: ['Este endereço está vinculado a um ou mais agendamentos.'],
        },
        message: 'Este endereço está vinculado a um ou mais agendamentos.',
        response: {
          status: 422,
          data: {
            message: 'Este endereço está vinculado a um ou mais agendamentos.',
            errors: {
              address: ['Este endereço está vinculado a um ou mais agendamentos.'],
            },
          },
        },
      });

      await expect(addressService.delete(1)).rejects.toThrow(
        'Este endereço está vinculado a um ou mais agendamentos.'
      );
    });
  });

  describe('setDefault', () => {
    it('calls PATCH /v1/addresses/{id}/default and returns the address', async () => {
      (apiClient.patch as jest.Mock).mockResolvedValueOnce({ data: { data: mockAddress } });

      const result = await addressService.setDefault(1);

      expect(apiClient.patch).toHaveBeenCalledWith('/v1/addresses/1/default');
      expect(result).toEqual(mockAddress);
    });
  });
});

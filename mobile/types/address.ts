/**
 * Address types - mirrors backend AddressResource
 * (web/app/Http/Resources/Api/V1/AddressResource.php)
 */

export interface Address {
  id: number;
  label: string;
  cep: string;
  uf: string;
  city: string;
  neighborhood: string;
  street: string;
  number: string;
  complement?: string | null;
  formatted_address: string;
  is_default: boolean;
}

export interface CreateAddressData {
  label: string;
  cep: string;
  uf: string;
  city: string;
  neighborhood: string;
  street: string;
  number: string;
  complement?: string;
}

export type UpdateAddressData = CreateAddressData;

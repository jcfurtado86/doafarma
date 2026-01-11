export interface Drug {
  id: number;
  product_name: string;
  substance: string;
  laboratory: string;
}

export interface MedicationOffering {
  id: number;
  lot_number: string;
  expires_at: string;
  quantity: number;
  drug?: Drug;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface CreateMedicationOfferingData {
  drug_id: number;
  lot_number: string;
  expires_at: string;
  quantity: number;
}

export interface UpdateMedicationOfferingData {
  lot_number?: string;
  expires_at?: string;
  quantity?: number;
}

// Search Result Types
export interface DrugSearchResult {
  id: number;
  product_name: string;
  substance: string;
  presentation: string;
  laboratory: string;
}

export interface DoctorSearchResult {
  id: number;
  name: string;
}

export type MedicationOfferingStatus = 'available' | 'reserved' | 'completed';

export interface MedicationOfferingSearchResult {
  id: number;
  quantity: number;
  lot_number: string;
  expires_at: string;
  status: MedicationOfferingStatus;
  drug: DrugSearchResult;
  doctor: DoctorSearchResult;
}
